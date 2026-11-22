<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAttendanceRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('take-attendance') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(digitFields: ['class_id', 'section_id', 'date']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')->where('school_id', $schoolId)],
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:today'],
        ];
    }
}
