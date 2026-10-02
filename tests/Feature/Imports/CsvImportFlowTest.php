<?php

declare(strict_types=1);

use App\Domain\Customers\Actions\RegisterCustomer;
use App\Domain\Customers\BrandMember;
use App\Domain\Customers\CiHasher;
use App\Domain\Customers\CustomerRecord;
use App\Domain\Imports\Actions\ProcessImportRow;
use App\Domain\Imports\CsvImport;
use App\Domain\Imports\Enums\ImportStatus;
use App\Domain\Imports\Enums\ImportType;
use App\Domain\Imports\Enums\PendingEventStatus;
use App\Domain\Imports\Jobs\ProcessCsvImport;
use App\Domain\Imports\PendingEvent;
use App\Domain\Imports\Support\ColumnMapper;
use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointRule;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function runImport(Brand $brand, ImportType $type, string $filename, string $contents): CsvImport
{
    Storage::disk('local')->put('imports/'.$filename, $contents);

    $import = CsvImport::withoutGlobalScopes()->create([
        'brand_id' => $brand->id,
        'type' => $type,
        'filename' => $filename,
        'file_path' => 'imports/'.$filename,
        'status' => ImportStatus::Pending,
    ]);

    (new ProcessCsvImport((int) $import->getKey()))->handle(
        app(ProcessImportRow::class),
        app(ColumnMapper::class),
    );

    return $import->refresh();
}

it('importa clientes y la reimportacion es idempotente', function (): void {
    Storage::fake('local');
    $brand = Brand::factory()->create();

    $csv = "ci,nombres,apellidos,email,telefono,fecha_registro\n"
        ."8000111,Ana,Gomez,ana@test.test,71111111,2026-01-10\n"
        ."8000222,Luis,Paz,luis@test.test,72222222,2026-02-11\n";

    $import = runImport($brand, ImportType::Customers, 'clientes.csv', $csv);

    expect($import->status)->toBe(ImportStatus::Done)
        ->and($import->rows_total)->toBe(2)
        ->and($import->rows_ok)->toBe(2)
        ->and($import->rows_failed)->toBe(0)
        ->and(CustomerRecord::withoutGlobalScopes()->where('brand_id', $brand->id)->count())->toBe(2);

    runImport($brand, ImportType::Customers, 'clientes.csv', $csv);

    expect(CustomerRecord::withoutGlobalScopes()->where('brand_id', $brand->id)->count())->toBe(2);
});

it('reporta filas invalidas sin abortar el archivo', function (): void {
    Storage::fake('local');
    $brand = Brand::factory()->create();

    $csv = "ci,nombres,apellidos,email,telefono,fecha_registro\n"
        ."8000333,Valida,Ok,v@test.test,70000000,2026-01-01\n"
        .",Sin,Ci,x@test.test,70000001,2026-01-01\n";

    $import = runImport($brand, ImportType::Customers, 'mixto.csv', $csv);

    expect($import->status)->toBe(ImportStatus::Done)
        ->and($import->rows_ok)->toBe(1)
        ->and($import->rows_failed)->toBe(1)
        ->and($import->errors()->count())->toBe(1);
});

it('guarda una compra pendiente y la acredita al reclamar la cuenta', function (): void {
    Storage::fake('local');
    $brand = Brand::factory()->create();

    PointRule::factory()->create([
        'brand_id' => $brand->id,
        'event_type' => RuleEventType::Purchase,
        'calc_type' => CalcType::Fixed,
        'value' => 100,
        'expires_after_days' => 365,
    ]);

    $customers = "ci,nombres,apellidos,email,telefono,fecha_registro\n"
        ."8000444,Carla,Nunez,carla@test.test,74444444,2026-01-01\n";
    runImport($brand, ImportType::Customers, 'clientes.csv', $customers);

    $purchases = "ci,numero_factura,fecha,monto\n"
        ."8000444,F-500,2026-02-01,150.00\n";
    $import = runImport($brand, ImportType::Purchases, 'compras.csv', $purchases);

    expect($import->rows_ok)->toBe(1);

    $pending = PendingEvent::withoutGlobalScopes()
        ->where('brand_id', $brand->id)
        ->where('status', PendingEventStatus::Pending->value)
        ->first();

    expect($pending)->not->toBeNull()
        ->and(PointLedger::withoutGlobalScopes()->count())->toBe(0);

    $result = app(RegisterCustomer::class)->handle($brand, [
        'name' => 'Carla Nunez',
        'email' => 'carla@test.test',
        'password' => 'secret123',
        'ci' => '8000444',
        'phone' => '74444444',
    ]);

    expect($result['linked'])->toBeTrue()
        ->and($result['credited'])->toBe(1)
        ->and($result['member']->balance())->toBe(100)
        ->and(PendingEvent::withoutGlobalScopes()->whereKey($pending->getKey())->first()->status)
        ->toBe(PendingEventStatus::Credited)
        ->and(PointLedger::withoutGlobalScopes()
            ->where('brand_member_id', $result['member']->getKey())
            ->where('type', LedgerType::Earn->value)
            ->count())->toBe(1);
});

it('reimportar compras no duplica puntos', function (): void {
    Storage::fake('local');
    $brand = Brand::factory()->create();

    $ci = '8000777';
    $hasher = app(CiHasher::class);
    $user = User::factory()->create(['ci' => $ci, 'ci_hash' => $hasher->hash($ci)]);
    $member = BrandMember::factory()->forBrand($brand)->forUser($user)->create();

    PointRule::factory()->create([
        'brand_id' => $brand->id,
        'event_type' => RuleEventType::Purchase,
        'calc_type' => CalcType::Fixed,
        'value' => 25,
        'expires_after_days' => 365,
    ]);

    $csv = "ci,numero_factura,fecha,monto\n"
        .$ci.",F-777,2026-02-01,50.00\n";

    runImport($brand, ImportType::Purchases, 'compras1.csv', $csv);
    runImport($brand, ImportType::Purchases, 'compras2.csv', $csv);

    expect(PointLedger::withoutGlobalScopes()
        ->where('brand_member_id', $member->getKey())
        ->where('type', LedgerType::Earn->value)
        ->count())->toBe(1);
});
