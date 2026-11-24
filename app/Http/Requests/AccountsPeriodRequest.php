<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Month (YYYY-MM) and date (YYYY-MM-DD) pickers for the summary and the cash book.
 */
class AccountsPeriodRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('view-accounts') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(digitFields: ['month', 'date']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function month(): CarbonImmutable
    {
        $value = $this->validated('month');

        return $value ? CarbonImmutable::createFromFormat('Y-m-d', $value.'-01')->startOfDay() : CarbonImmutable::today()->startOfMonth();
    }

    public function selectedDate(): CarbonImmutable
    {
        $value = $this->validated('date');

        return $value ? CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay() : CarbonImmutable::today();
    }
}
