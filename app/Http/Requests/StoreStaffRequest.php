<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('manage-staff') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(
            textFields: ['name', 'name_bn', 'designation'],
            digitFields: ['joined_on', 'salary'],
            mobileFields: ['phone'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'joined_on' => ['nullable', 'date_format:Y-m-d'],
            'salary' => ['required', 'string', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * Staff columns built from the validated input, with the salary converted to poisha.
     *
     * @return array<string, mixed>
     */
    public function staffData(): array
    {
        $data = $this->validated();
        $data['salary_basic'] = Money::toPoisha($data['salary']);
        unset($data['salary']);

        return $data;
    }
}
