<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Support\Enums\AttendanceStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    use NormalizesInput;

    public const MAX_ROWS = 500;

    public function authorize(): bool
    {
        return $this->user()?->can('take-attendance') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(digitFields: ['date']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:today'],
            'attendance' => ['required', 'array', 'min:1', 'max:'.self::MAX_ROWS],
            'attendance.*' => ['required', Rule::enum(AttendanceStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $attendance = $this->input('attendance');

            if (! is_array($attendance)) {
                return;
            }

            foreach (array_keys($attendance) as $key) {
                if (! is_int($key)) {
                    $validator->errors()->add('attendance', 'Invalid enrollment id.');

                    return;
                }
            }
        });
    }
}
