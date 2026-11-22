<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Fees\Models\Invoice;
use App\Domain\Payments\Models\Payment;
use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Enums\PaymentMethod;
use App\Support\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'invoice_id' => Invoice::factory(),
            'method' => PaymentMethod::Cash,
            'amount' => 100000,
            'gateway_txn_id' => null,
            'status' => PaymentStatus::Completed,
            'paid_at' => now(),
            'collected_by' => User::factory(),
            'notes' => 'Full payment received',
        ];
    }
}
