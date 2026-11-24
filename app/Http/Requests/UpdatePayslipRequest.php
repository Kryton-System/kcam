<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Payroll\Models\Payslip;
use App\Http\Requests\Concerns\NormalizesInput;
use App\Support\Money;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePayslipRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('manage-payroll') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(textFields: ['notes'], digitFields: ['deductions', 'advances']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'deductions' => ['required', 'string', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'advances' => ['required', 'string', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payslip = $this->route('payslip');

            if (! $payslip instanceof Payslip || $validator->errors()->isNotEmpty()) {
                return;
            }

            if ($payslip->isPaid()) {
                $validator->errors()->add('deductions', 'A paid payslip cannot be changed.');

                return;
            }

            $total = Money::toPoisha((string) $this->input('deductions')) + Money::toPoisha((string) $this->input('advances'));

            if ($total > $payslip->basic) {
                $validator->errors()->add('deductions', 'Deductions plus advances cannot exceed the basic salary.');
            }
        });
    }
}
