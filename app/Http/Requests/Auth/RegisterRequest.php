<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Customers\CiHasher;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'ci' => ['required', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'ref' => ['nullable', 'string', 'max:16'],
        ];
    }

    /**
     * El CI se guarda cifrado, por lo que la unicidad se valida con su hash.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ci = (string) $this->input('ci');

            if ($ci === '') {
                return;
            }

            $hash = app(CiHasher::class)->hash($ci);

            if (User::query()->where('ci_hash', $hash)->exists()) {
                $validator->errors()->add('ci', 'Esta cedula de identidad ya esta registrada.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contrasena',
            'ci' => 'cedula de identidad',
            'phone' => 'telefono',
            'birth_date' => 'fecha de nacimiento',
        ];
    }
}
