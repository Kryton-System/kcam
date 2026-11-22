<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Receipt;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'payment_id' => Payment::factory(),
            'serial_no' => 'REC-2026-'.fake()->unique()->numerify('#####'),
            'pdf_path' => null,
        ];
    }
}
