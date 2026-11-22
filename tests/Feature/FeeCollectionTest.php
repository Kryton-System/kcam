<?php

declare(strict_types=1);

use App\Domain\Fees\Actions\CollectFeeAction;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Payments\Models\Receipt;
use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Enums\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('collect fee action records payment, creates receipt, and updates invoice dues', function (): void {
    $school = School::factory()->create();
    $accountant = User::factory()->accountant()->create(['school_id' => $school->id]);

    $invoice = Invoice::factory()->create([
        'school_id' => $school->id,
        'total' => 120000,
        'paid' => 0,
        'due' => 120000,
        'status' => 'unpaid',
    ]);

    $action = app(CollectFeeAction::class);

    // Collect first installment: 500 BDT
    $receipt1 = $action->execute(
        invoice: $invoice,
        amount: 50000,
        method: PaymentMethod::Cash,
        collector: $accountant,
        notes: 'First installment',
    );

    expect($receipt1)->toBeInstanceOf(Receipt::class)
        ->and($receipt1->serial_no)->toBe('REC-'.date('Y').'-00001');

    $invoice->refresh();
    expect($invoice->paid)->toBe(50000)
        ->and($invoice->due)->toBe(70000)
        ->and($invoice->status)->toBe('partial');

    // Collect second installment: 700 BDT (full remaining due)
    $receipt2 = $action->execute(
        invoice: $invoice,
        amount: 70000,
        method: PaymentMethod::Cash,
        collector: $accountant,
        notes: 'Final installment',
    );

    expect($receipt2->serial_no)->toBe('REC-'.date('Y').'-00002');

    $invoice->refresh();
    expect($invoice->paid)->toBe(120000)
        ->and($invoice->due)->toBe(0)
        ->and($invoice->status)->toBe('paid');
});

test('collect fee action throws exception when amount exceeds remaining due', function (): void {
    $school = School::factory()->create();
    $accountant = User::factory()->accountant()->create(['school_id' => $school->id]);

    $invoice = Invoice::factory()->create([
        'school_id' => $school->id,
        'total' => 100000,
        'paid' => 0,
        'due' => 100000,
        'status' => 'unpaid',
    ]);

    $action = app(CollectFeeAction::class);

    expect(fn () => $action->execute(
        invoice: $invoice,
        amount: 150000, // exceeds due
        method: PaymentMethod::Cash,
        collector: $accountant
    ))->toThrow(InvalidArgumentException::class, 'Payment amount exceeds remaining invoice due amount.');
});
