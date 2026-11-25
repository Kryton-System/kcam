<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexStudentsRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('view-students') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(textFields: ['search'], digitFields: ['class_id']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $this->user()->school_id)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['name', 'code', 'newest'])],
        ];
    }
}
