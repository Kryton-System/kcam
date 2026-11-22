<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexFeesRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('collect-fees') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(textFields: ['search']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['unpaid', 'paid', 'all'])],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
