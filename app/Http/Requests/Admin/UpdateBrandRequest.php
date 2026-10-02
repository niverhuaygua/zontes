<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Brand|null $brand */
        $brand = $this->route('brand');

        return $brand !== null && ($this->user()?->isSuperAdmin() ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'short_name' => ['nullable', 'string', 'max:40'],
            'primary' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'primary_dark' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
