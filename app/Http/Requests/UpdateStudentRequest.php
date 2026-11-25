<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Support\Enums\Religion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('edit-students') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(
            textFields: ['name', 'name_bn', 'guardian_name', 'blood_group', 'address'],
            digitFields: ['dob', 'birth_reg_no', 'guardian_nid'],
            mobileFields: ['guardian_mobile'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $schoolId = $this->user()->school_id;
        $studentId = $this->route('student')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date_format:Y-m-d', 'before:today', 'after:'.now()->subYears(25)->toDateString()],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'address' => ['nullable', 'string', 'max:500'],
            'birth_reg_no' => [
                'nullable',
                'digits:17',
                Rule::unique('students', 'birth_reg_no')
                    ->where('school_id', $schoolId)
                    ->whereNull('deleted_at')
                    ->ignore($studentId),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_relation' => ['required', Rule::in(['father', 'mother', 'other'])],
            'guardian_mobile' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'guardian_nid' => ['nullable', 'regex:/^(?:\d{10}|\d{13}|\d{17})$/'],
            'guardian_nid_front' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'guardian_nid_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guardian_mobile.regex' => __('students.errors.invalid_mobile'),
        ];
    }
}
