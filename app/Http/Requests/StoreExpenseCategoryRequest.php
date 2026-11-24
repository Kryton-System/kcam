<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseCategoryRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('manage-expenses') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(textFields: ['name', 'name_bn']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('expense_categories', 'name')->where('school_id', $this->user()->school_id),
            ],
            'name_bn' => ['nullable', 'string', 'max:100'],
        ];
    }
}
