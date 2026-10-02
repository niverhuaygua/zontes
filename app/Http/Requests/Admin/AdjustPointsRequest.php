<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustPointsRequest extends FormRequest
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
        return [
            'brand_member_id' => ['required', 'integer', 'exists:brand_members,id'],
            'points' => ['required', 'integer', 'not_in:0'],
            'note' => ['required', 'string', 'min:5', 'max:180'],
        ];
    }
}
