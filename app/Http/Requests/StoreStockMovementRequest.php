<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Support\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    use NormalizesInput;

    public const MAX_QUANTITY = 1000000;

    public function authorize(): bool
    {
        return $this->user()?->can('manage-inventory') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(
            textFields: ['reason', 'note'],
            digitFields: ['quantity'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isAdjustment = $this->input('type') === StockMovementType::Adjustment->value;

        return [
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => $isAdjustment
                ? ['required', 'integer', 'not_in:0', 'between:-'.self::MAX_QUANTITY.','.self::MAX_QUANTITY]
                : ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
            'reason' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
