<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\School\Models\Subject;
use App\Domain\Student\Models\Enrollment;
use App\Support\Helpers\BanglaHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('enter-marks') ?? false) && $this->route('exam')->published_at === null;
    }

    protected function prepareForValidation(): void
    {
        $marks = $this->input('marks');

        if (is_array($marks)) {
            $this->merge(['marks' => array_map(
                fn ($v) => is_string($v) ? BanglaHelper::toEnglishDigits(trim($v)) : $v,
                $marks,
            )]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $subject = Subject::query()->find((int) $this->input('subject_id'));

        return [
            'subject_id' => [
                'required', 'integer',
                Rule::exists('subjects', 'id')->where('school_id', $this->user()->school_id),
            ],
            'marks' => ['required', 'array'],
            'marks.*' => ['nullable', 'numeric', 'min:0', 'max:'.($subject?->full_marks ?? 100)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $subject = Subject::query()->find((int) $this->input('subject_id'));
            $marks = $this->input('marks');

            if (! $subject || ! is_array($marks)) {
                return;
            }

            $valid = Enrollment::query()
                ->where('class_id', $subject->class_id)
                ->where('academic_year_id', $this->route('exam')->academic_year_id)
                ->whereIn('id', array_map('intval', array_keys($marks)))
                ->pluck('id')->all();

            foreach (array_keys($marks) as $id) {
                if (! in_array((int) $id, $valid, true)) {
                    $validator->errors()->add("marks.$id", 'Invalid student for this subject.');
                }
            }
        }];
    }
}
