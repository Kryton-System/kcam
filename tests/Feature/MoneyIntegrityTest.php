<?php

declare(strict_types=1);

use App\Domain\Fees\Actions\CollectFeeAction;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Fees\Models\InvoiceItem;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Receipt;
use App\Domain\School\Models\School;
use App\Domain\Student\Models\Student;
use App\Support\Enums\PaymentMethod;
use App\Support\Enums\UserRole;
use App\Support\Money;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

describe('Money helper', function (): void {
    test('taka strings become integer poisha without floats', function (string $taka, int $poisha): void {
        expect(Money::toPoisha($taka))->toBe($poisha);
    })->with([
        ['500', 50000],
        ['500.5', 50050],
        ['500.50', 50050],
        ['0.01', 1],
        ['0.1', 10],
        ['19.99', 1999],
        ['1234567.89', 123456789],
    ]);

    test('invalid taka strings are rejected', function (string $taka): void {
        expect(fn () => Money::toPoisha($taka))->toThrow(InvalidArgumentException::class);
    })->with(['1.234', '-5', 'abc', '', '1e3', '1,000', '.5']);

    test('poisha format as a taka string', function (): void {
        expect(Money::toTaka(50050))->toBe('500.50')
            ->and(Money::toTaka(5))->toBe('0.05')
            ->and(Money::toTaka(0))->toBe('0.00')
            ->and(Money::toTaka(100000))->toBe('1000.00');
    });

    test('percentage discounts round half up in whole poisha', function (): void {
        // 10% of 800.00 taka, rate stored in basis points
        expect(Money::percentOf(80000, 1000))->toBe(8000)
            // 12.5% of 0.05 taka = 0.625 poisha -> rounds to 1
            ->and(Money::percentOf(5, 1250))->toBe(1)
            // 33.33% of 100.00 taka
            ->and(Money::percentOf(10000, 3333))->toBe(3333);
    });
});

describe('money columns are integers', function (): void {
    test('invoice amounts come back as integers', function (): void {
        $school = School::factory()->create();
        $invoice = Invoice::factory()->create(['school_id' => $school->id, 'total' => 123456, 'paid' => 0, 'due' => 123456]);

        $fresh = CurrentSchool::runAs($school->id, fn (): Invoice => Invoice::findOrFail($invoice->id));

        expect($fresh->total)->toBeInt()->toBe(123456)
            ->and($fresh->due)->toBeInt();
    });

    test('money columns are stored as integer types, not decimals', function (string $table, string $column): void {
        expect(Schema::getColumnType($table, $column))->toContain('int');
    })->with([
        ['invoices', 'total'],
        ['invoices', 'paid'],
        ['invoices', 'due'],
        ['invoice_items', 'amount'],
        ['payments', 'amount'],
        ['fee_structures', 'amount'],
        ['fee_discounts', 'value'],
        ['staff', 'salary_basic'],
    ]);
});

describe('receipts', function (): void {
    /**
     * @return array{0: School, 1: Invoice}
     */
    function schoolWithInvoice(int $due = 100000): array
    {
        $school = School::factory()->create();
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'total' => $due,
            'paid' => 0,
            'due' => $due,
            'status' => 'unpaid',
        ]);

        return [$school, $invoice];
    }

    test('serial numbers come from a locked per school counter and stay gapless', function (): void {
        [$school, $invoice] = schoolWithInvoice(100000);
        $action = app(CollectFeeAction::class);
        $accountant = schoolUser(UserRole::Accountant, $school);

        $first = $action->execute($invoice, 10000, PaymentMethod::Cash, $accountant);

        // A rejected payment must not burn a serial number.
        expect(fn () => $action->execute($invoice->fresh(), 99999999, PaymentMethod::Cash, $accountant))
            ->toThrow(InvalidArgumentException::class);

        $second = $action->execute($invoice->fresh(), 10000, PaymentMethod::Cash, $accountant);

        expect($first->serial_no)->toBe('REC-'.date('Y').'-00001')
            ->and($second->serial_no)->toBe('REC-'.date('Y').'-00002')
            ->and((int) DB::table('receipt_sequences')->where('school_id', $school->id)->value('next_no'))->toBe(3);
    });

    test('each school has its own serial sequence', function (): void {
        [$schoolA, $invoiceA] = schoolWithInvoice();
        [$schoolB, $invoiceB] = schoolWithInvoice();
        $action = app(CollectFeeAction::class);

        $a = $action->execute($invoiceA, 10000, PaymentMethod::Cash, schoolUser(UserRole::Accountant, $schoolA));
        $b = $action->execute($invoiceB, 10000, PaymentMethod::Cash, schoolUser(UserRole::Accountant, $schoolB));

        expect($a->serial_no)->toBe($b->serial_no)->toBe('REC-'.date('Y').'-00001');
    });

    test('the same client uuid never collects twice', function (): void {
        [$school, $invoice] = schoolWithInvoice(100000);
        $action = app(CollectFeeAction::class);
        $accountant = schoolUser(UserRole::Accountant, $school);
        $uuid = (string) Str::uuid();

        $first = $action->execute($invoice, 40000, PaymentMethod::Cash, $accountant, clientUuid: $uuid);
        $again = $action->execute($invoice->fresh(), 40000, PaymentMethod::Cash, $accountant, clientUuid: $uuid);

        expect($again->id)->toBe($first->id)
            ->and(Payment::withoutGlobalScope('school')->count())->toBe(1)
            ->and(Receipt::withoutGlobalScope('school')->count())->toBe(1)
            ->and($invoice->fresh()->paid)->toBe(40000);
    });

    test('a double submitted web form collects once', function (): void {
        [$school, $invoice] = schoolWithInvoice(100000);
        $accountant = schoolUser(UserRole::Accountant, $school);
        $payload = ['amount' => '400.50', 'method' => 'cash', 'client_uuid' => (string) Str::uuid()];

        $this->actingAs($accountant)->post("/fees/{$invoice->id}/collect", $payload)->assertSessionHasNoErrors();
        $this->actingAs($accountant)->post("/fees/{$invoice->id}/collect", $payload)->assertSessionHasNoErrors();

        expect($invoice->fresh()->paid)->toBe(40050)
            ->and(Payment::withoutGlobalScope('school')->count())->toBe(1);
    });

    test('the web amount in taka is stored as poisha', function (): void {
        [$school, $invoice] = schoolWithInvoice(100000);

        $this->actingAs(schoolUser(UserRole::Accountant, $school))
            ->post("/fees/{$invoice->id}/collect", ['amount' => '250.75', 'method' => 'cash'])
            ->assertSessionHasNoErrors();

        expect(Payment::withoutGlobalScope('school')->first()->amount)->toBe(25075)
            ->and($invoice->fresh()->due)->toBe(74925);
    });

    test('a payment never exceeds the remaining due', function (): void {
        [$school, $invoice] = schoolWithInvoice(100000);

        $this->actingAs(schoolUser(UserRole::Accountant, $school))
            ->post("/fees/{$invoice->id}/collect", ['amount' => '1000.01', 'method' => 'cash'])
            ->assertSessionHasErrors('amount');
    });
});

describe('money rows cannot be deleted through their parents', function (): void {
    test('a student with invoices cannot be force deleted', function (): void {
        [$school, $invoice] = schoolWithInvoice();
        $student = Student::withoutGlobalScope('school')->findOrFail($invoice->student_id);

        expect(fn () => $student->forceDelete())->toThrow(QueryException::class);
    });

    test('an invoice with payments cannot be force deleted', function (): void {
        [$school, $invoice] = schoolWithInvoice();
        app(CollectFeeAction::class)->execute($invoice, 10000, PaymentMethod::Cash, schoolUser(UserRole::Accountant, $school));

        expect(fn () => Invoice::withoutGlobalScope('school')->findOrFail($invoice->id)->forceDelete())
            ->toThrow(QueryException::class);
    });

    test('a payment with a receipt cannot be force deleted', function (): void {
        [$school, $invoice] = schoolWithInvoice();
        $receipt = app(CollectFeeAction::class)->execute($invoice, 10000, PaymentMethod::Cash, schoolUser(UserRole::Accountant, $school));

        expect(fn () => Payment::withoutGlobalScope('school')->findOrFail($receipt->payment_id)->forceDelete())
            ->toThrow(QueryException::class);
    });
});

describe('invoice integrity', function (): void {
    test('one invoice per student, academic year and month at database level', function (): void {
        [$school, $invoice] = schoolWithInvoice();

        expect(fn () => Invoice::factory()->create([
            'school_id' => $school->id,
            'student_id' => $invoice->student_id,
            'academic_year_id' => $invoice->academic_year_id,
            'month' => $invoice->month,
        ]))->toThrow(QueryException::class);
    });

    test('invoice items carry the school id and are tenant scoped', function (): void {
        [$school, $invoice] = schoolWithInvoice();
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'school_id' => $school->id, 'amount' => 50000]);

        expect($item->school_id)->toBe($school->id)
            ->and(CurrentSchool::runAs(School::factory()->create()->id, fn (): int => InvoiceItem::count()))->toBe(0);
    });

    test('a failed collection leaves no half written rows', function (): void {
        [$school, $invoice] = schoolWithInvoice(1000);

        try {
            app(CollectFeeAction::class)->execute($invoice, 5000, PaymentMethod::Cash, schoolUser(UserRole::Accountant, $school));
        } catch (InvalidArgumentException) {
            // expected
        }

        expect(Payment::withoutGlobalScope('school')->count())->toBe(0)
            ->and(Receipt::withoutGlobalScope('school')->count())->toBe(0)
            ->and(DB::table('receipt_sequences')->count())->toBe(0);
    });
});
