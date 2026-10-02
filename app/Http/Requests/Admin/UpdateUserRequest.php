<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Customers\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isSuperAdmin() || $user->role->isAdmin());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in(UserRole::values())],
            'brands' => ['nullable', 'array'],
            'brands.*' => ['integer', 'exists:brands,id'],
        ];
    }
}
