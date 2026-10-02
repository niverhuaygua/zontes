<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\Enums\RuleEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePointRuleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'event_type' => ['required', Rule::in(RuleEventType::values())],
            'calc_type' => ['required', Rule::in(CalcType::values())],
            'value' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'expires_after_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
