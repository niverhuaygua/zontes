<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Imports\Enums\ImportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('loyalty.imports.max_kilobytes', 10240);

        return [
            'type' => ['required', Rule::in(ImportType::values())],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.$maxKb],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'El archivo debe ser un CSV (o TXT delimitado).',
            'file.max' => 'El archivo supera el tamano maximo permitido.',
        ];
    }
}
