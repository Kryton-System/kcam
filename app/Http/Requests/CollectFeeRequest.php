<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Fees\Models\Invoice;
use App\Http\Requests\Concerns\NormalizesInput;
use App\Support\Enums\PaymentMethod;
use App\Support\Money;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectFeeRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('collect-fees') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(textFields: ['notes'], digitFields: ['amount']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'string', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'client_uuid' => ['nullable', 'uuid'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $invoice = $this->route('invoice');

            if (! $invoice instanceof Invoice || $validator->errors()->has('amount')) {
                return;
            }

            if ($invoice->status === 'cancelled') {
                $validator->errors()->add('amount', 'This invoice is cancelled and cannot be paid.');

                return;
            }

            $poisha = Money::toPoisha((string) $this->input('amount'));

            if ($poisha < 100) {
                $validator->errors()->add('amount', 'The minimum payment is 1 taka.');

                return;
            }

            if ($poisha > (int) $invoice->due) {
                $validator->errors()->add('amount', 'Payment amount exceeds remaining invoice due amount.');
            }
        });
    }
}
