<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Fees\Models\FeeHead;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Fees\Models\InvoiceItem;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'invoice_id' => Invoice::factory(),
            'fee_head_id' => FeeHead::factory(),
            'amount' => 50000,
        ];
    }
}
