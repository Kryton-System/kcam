<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('manage-expenses') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(
            textFields: ['payee', 'note'],
            digitFields: ['amount', 'spent_on', 'expense_category_id'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => [
                'required', 'integer',
                Rule::exists('expense_categories', 'id')->where('school_id', $this->user()->school_id),
            ],
            'spent_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'string', 'regex:/^\d{1,9}(\.\d{1,2})?$/', 'not_regex:/^0+(\.0{1,2})?$/'],
            'payee' => ['required', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
