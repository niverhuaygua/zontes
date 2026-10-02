<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9\-]+$/', 'unique:brands,slug'],
            'short_name' => ['nullable', 'string', 'max:40'],
            'primary' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'primary_dark' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
