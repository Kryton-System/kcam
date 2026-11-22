<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoticeRequest extends FormRequest
{
    use NormalizesInput;

    /** Longest body sent as an SMS: about seven Bangla (UCS-2) segments. */
    public const MAX_SMS_CHARACTERS = 480;

    public function authorize(): bool
    {
        return $this->user()?->can('send-notices') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(
            textFields: ['title', 'title_bn', 'body', 'body_bn'],
            digitFields: ['target_class_id'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'body_bn' => ['nullable', 'string', 'max:5000'],
            'audience' => ['required', Rule::in(['all', 'class', 'section', 'parents', 'teachers'])],
            'target_class_id' => [
                'nullable',
                'required_if:audience,class,section',
                'integer',
                Rule::exists('classes', 'id')->where('school_id', $this->user()->school_id),
            ],
            'channel' => ['required', Rule::in(['app', 'sms', 'both'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! in_array($this->input('channel'), ['sms', 'both'], true)) {
                return;
            }

            foreach (['body', 'body_bn'] as $field) {
                $value = $this->input($field);

                if (is_string($value) && mb_strlen($value) > self::MAX_SMS_CHARACTERS) {
                    $validator->errors()->add($field, 'An SMS notice can be at most '.self::MAX_SMS_CHARACTERS.' characters.');
                }
            }
        });
    }
}
