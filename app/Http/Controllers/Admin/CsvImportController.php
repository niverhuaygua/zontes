<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Brands\CurrentBrand;
use App\Domain\Imports\Actions\ProcessImportRow;
use App\Domain\Imports\CsvImport;
use App\Domain\Imports\Enums\ImportStatus;
use App\Domain\Imports\Enums\ImportType;
use App\Domain\Imports\Jobs\ProcessCsvImport;
use App\Domain\Imports\Support\ColumnMapper;
use App\Domain\Imports\Support\CsvReader;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCsvImportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvImportController extends Controller
{
    public function __construct(
        private readonly CurrentBrand $currentBrand,
        private readonly ColumnMapper $mapper,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', CsvImport::class);
        $brand = $this->currentBrand->require();

        return view('admin.imports.index', [
            'brand' => $brand,
            'imports' => CsvImport::withoutGlobalScopes()
                ->where('brand_id', $brand->getKey())
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', CsvImport::class);

        return view('admin.imports.create', [
            'brand' => $this->currentBrand->require(),
            'types' => ImportType::cases(),
        ]);
    }

    public function store(StoreCsvImportRequest $request): RedirectResponse
    {
        $this->authorize('create', CsvImport::class);
        $brand = $this->currentBrand->require();
        $type = ImportType::from((string) $request->string('type'));

        $file = $request->file('file');
        $path = $file->store('imports', (string) config('loyalty.imports.disk'));

        $import = CsvImport::withoutGlobalScopes()->create([
            'brand_id' => $brand->getKey(),
            'type' => $type,
            'filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => ImportStatus::Pending,
            'user_id' => $request->user()?->getKey(),
        ]);

        $reader = new CsvReader;

        try {
            $reader->open($import->absolutePath());
            $headers = $reader->headers();
            $missing = $this->mapper->missing($type, $headers);

            if ($missing !== []) {
                $import->update([
                    'status' => ImportStatus::Failed,
                    'error_message' => 'Faltan columnas obligatorias: '.implode(', ', $missing).'.',
                    'finished_at' => now(),
                ]);

                return redirect()->route('admin.imports.show', $import)
                    ->withErrors(['file' => $import->error_message]);
            }

            $map = $this->mapper->resolve($type, $headers);
            $preview = $this->buildPreview($reader, $map);

            $import->update([
                'column_map' => $map,
                'delimiter' => $reader->delimiter(),
                'preview' => $preview,
            ]);
        } finally {
            $reader->close();
        }

        return redirect()->route('admin.imports.show', $import)
            ->with('status', 'Archivo cargado. Revisa la vista previa y confirma el procesamiento.');
    }

    public function show(CsvImport $import): View
    {
        $this->authorize('view', $import);

        return view('admin.imports.show', [
            'brand' => $this->currentBrand->require(),
            'import' => $import->load('errors'),
        ]);
    }

    public function confirm(Request $request, CsvImport $import, ProcessImportRow $processor): RedirectResponse
    {
        $this->authorize('process', $import);

        if ($import->isProcessed() || $import->status === ImportStatus::Processing) {
            return redirect()->route('admin.imports.show', $import)
                ->with('status', 'Esta importacion ya fue procesada.');
        }

        $import->update(['confirmed_at' => now()]);

        if (config('queue.default') === 'sync') {
            (new ProcessCsvImport((int) $import->getKey()))->handle($processor, $this->mapper);
        } else {
            ProcessCsvImport::dispatch((int) $import->getKey());
        }
        $import->refresh();

        return redirect()->route('admin.imports.show', $import)
            ->with('status', $import->isProcessed()
                ? "Procesado: {$import->rows_ok} filas correctas, {$import->rows_failed} con error."
                : 'La importacion quedo en cola y se procesara en segundo plano.');
    }

    public function downloadErrors(CsvImport $import): StreamedResponse
    {
        $this->authorize('view', $import);

        return response()->streamDownload(function () use ($import): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['fila', 'ci', 'error']);

            foreach ($import->errors()->orderBy('row_number')->cursor() as $error) {
                fputcsv($out, [$error->row_number, $error->ci, $error->message]);
            }

            fclose($out);
        }, "errores_import_{$import->getKey()}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, string>  $map
     * @return array<int, array<string, string|null>>
     */
    private function buildPreview(CsvReader $reader, array $map): array
    {
        $rows = [];
        $limit = (int) config('loyalty.imports.preview_rows', 10);

        $reader->eachRow(function (int $number, array $row) use (&$rows, $map, $limit): bool {
            $canonical = ['_row' => (string) $number];
            foreach ($map as $name => $header) {
                $canonical[$name] = $row[$header] ?? null;
            }
            $rows[] = $canonical;

            return count($rows) < $limit;
        });

        return $rows;
    }
}
