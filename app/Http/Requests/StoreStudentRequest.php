<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('create-students') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(
            textFields: ['name', 'name_bn', 'guardian_name', 'blood_group'],
            digitFields: ['dob', 'roll_no', 'class_id', 'section_id'],
            mobileFields: ['guardian_mobile'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date_format:Y-m-d', 'before:today', 'after:'.now()->subYears(25)->toDateString()],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_relation' => ['required', Rule::in(['father', 'mother', 'other'])],
            'guardian_mobile' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'section_id' => [
                'nullable',
                'integer',
                Rule::exists('sections', 'id')
                    ->where('school_id', $schoolId)
                    ->where('class_id', $this->input('class_id')),
            ],
            'roll_no' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guardian_mobile.regex' => 'সঠিক মোবাইল নম্বর দিন (যেমন 017XXXXXXXX)।',
        ];
    }
}
