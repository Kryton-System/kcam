<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Student\Models\Enrollment;
use App\Http\Requests\Concerns\NormalizesInput;
use App\Support\Money;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class AssignStudentRequest extends FormRequest
{
    use NormalizesInput;

    /**
     * The transport_assignments.monthly_fee column is an unsigned 32-bit integer in poisha,
     * so the largest storable fee is 40,000,000 taka.
     */
    public const MAX_FEE_POISHA = 4_000_000_000;

    public function authorize(): bool
    {
        return $this->user()?->can('manage-transport') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(
            textFields: ['stop'],
            digitFields: ['enrollment_id', 'monthly_fee'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'integer'],
            'stop' => ['nullable', 'string', 'max:150'],
            'monthly_fee' => ['required', 'string', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('monthly_fee')) {
                return;
            }

            if (Money::toPoisha((string) $this->input('monthly_fee')) > self::MAX_FEE_POISHA) {
                $validator->errors()->add('monthly_fee', __('transport.errors.fee_too_high'));
            }
        });

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('enrollment_id')) {
                return;
            }

            $currentYearId = $this->user()->school?->currentAcademicYear()?->id;
            $valid = $currentYearId !== null && Enrollment::query()
                ->whereKey((int) $this->input('enrollment_id'))
                ->where('academic_year_id', $currentYearId)
                ->where('status', 'active')
                ->exists();

            if (! $valid) {
                $validator->errors()->add('enrollment_id', __('transport.errors.choose_active_student'));
            }
        });
    }

    /**
     * The validated monthly fee as whole poisha.
     *
     * @throws InvalidArgumentException when called before validation passed
     */
    public function feePoisha(): int
    {
        return Money::toPoisha((string) $this->validated('monthly_fee'));
    }
}
