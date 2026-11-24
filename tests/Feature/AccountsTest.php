<?php

declare(strict_types=1);

use App\Domain\Accounts\Actions\BuildAccountsSummary;
use App\Domain\Accounts\Models\Expense;
use App\Domain\Accounts\Models\ExpenseCategory;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Payments\Models\Payment;
use App\Domain\School\Models\School;
use App\Domain\Student\Models\Student;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Spatie\Activitylog\Models\Activity;

function accountsCategory(School $school, string $name = 'Stationery', ?string $nameBn = 'স্টেশনারি'): ExpenseCategory
{
    return CurrentSchool::runAs($school->id, fn () => ExpenseCategory::factory()->create([
        'school_id' => $school->id, 'name' => $name, 'name_bn' => $nameBn,
    ]));
}

function accountsExpense(School $school, ExpenseCategory $category, int $amount, string $date, array $extra = []): Expense
{
    return CurrentSchool::runAs($school->id, fn () => Expense::factory()->create([
        'school_id' => $school->id, 'expense_category_id' => $category->id,
        'amount' => $amount, 'spent_on' => $date,
    ] + $extra));
}

function accountsPayment(School $school, int $amount, string $paidAt, PaymentStatus $status = PaymentStatus::Completed): Payment
{
    return CurrentSchool::runAs($school->id, function () use ($school, $amount, $paidAt, $status): Payment {
        $student = Student::factory()->create(['school_id' => $school->id]);
        $invoice = Invoice::factory()->create(['school_id' => $school->id, 'student_id' => $student->id]);

        return Payment::factory()->create([
            'school_id' => $school->id, 'invoice_id' => $invoice->id,
            'amount' => $amount, 'paid_at' => $paidAt, 'status' => $status,
        ]);
    });
}

function expensePayload(ExpenseCategory $category, array $override = []): array
{
    return $override + [
        'expense_category_id' => $category->id,
        'spent_on' => now()->toDateString(),
        'amount' => '1250.50',
        'payee' => 'Rahim Traders',
        'note' => 'Chalk and markers',
    ];
}

// Authorization

test('owner and accountant can open every accounts page', function (UserRole $role): void {
    $user = schoolUser($role);

    foreach (['accounts.index', 'accounts.expenses', 'accounts.cash-book'] as $name) {
        $this->actingAs($user)->get(route($name))->assertOk();
    }
})->with([UserRole::Owner, UserRole::Accountant]);

test('teacher and parent are denied the accounts pages and actions', function (UserRole $role): void {
    $user = schoolUser($role);
    $category = accountsCategory($user->school);
    $expense = accountsExpense($user->school, $category, 1000, now()->toDateString());

    foreach (['accounts.index', 'accounts.expenses', 'accounts.cash-book'] as $name) {
        $this->actingAs($user)->get(route($name))->assertForbidden();
    }

    $this->actingAs($user)->post(route('accounts.expenses.store'), expensePayload($category))->assertForbidden();
    $this->actingAs($user)->post(route('accounts.expenses.void', $expense), ['reason' => 'Wrong entry'])->assertForbidden();
    $this->actingAs($user)->post(route('accounts.categories.store'), ['name' => 'Rent'])->assertForbidden();
})->with([UserRole::Teacher, UserRole::Parent]);

test('guests are redirected to login', function (): void {
    $this->get(route('accounts.index'))->assertRedirect(route('login'));
});

test('module switch off hides the accounts routes and nav link', function (): void {
    config(['kcam.modules.accounts' => false]);
    $owner = schoolUser(UserRole::Owner);

    $this->actingAs($owner)->get(route('accounts.index'))->assertNotFound();
    $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertDontSee(route('accounts.index'));
});

test('nav link shows when the module is on', function (): void {
    $this->actingAs(schoolUser(UserRole::Accountant))->get(route('dashboard'))->assertSee(route('accounts.index'));
});

// Recording expenses

test('accountant records an expense in poisha and it is activity logged', function (): void {
    $accountant = schoolUser(UserRole::Accountant);
    $category = accountsCategory($accountant->school);

    $this->actingAs($accountant)->post(route('accounts.expenses.store'), expensePayload($category))->assertRedirect();

    $expense = CurrentSchool::runAs($accountant->school_id, fn () => Expense::first());
    expect($expense->amount)->toBe(125050)
        ->and($expense->payee)->toBe('Rahim Traders')
        ->and($expense->recorded_by)->toBe($accountant->id)
        ->and($expense->isVoided())->toBeFalse()
        ->and(Activity::where('description', 'expense_recorded')->count())->toBe(1);
});

test('bangla digits are accepted for the amount', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $category = accountsCategory($owner->school);

    $this->actingAs($owner)->post(route('accounts.expenses.store'), expensePayload($category, ['amount' => '৫০০']))->assertRedirect();

    expect(CurrentSchool::runAs($owner->school_id, fn () => Expense::first()->amount))->toBe(50000);
});

test('invalid expense input is rejected', function (array $override, string $field): void {
    $owner = schoolUser(UserRole::Owner);
    $category = accountsCategory($owner->school);

    $this->actingAs($owner)->post(route('accounts.expenses.store'), expensePayload($category, $override))
        ->assertSessionHasErrors($field);

    expect(CurrentSchool::runAs($owner->school_id, fn () => Expense::count()))->toBe(0);
})->with([
    'zero amount' => [['amount' => '0'], 'amount'],
    'zero decimal amount' => [['amount' => '0.00'], 'amount'],
    'negative amount' => [['amount' => '-5'], 'amount'],
    'three decimals' => [['amount' => '10.123'], 'amount'],
    'text amount' => [['amount' => 'abc'], 'amount'],
    'missing payee' => [['payee' => ''], 'payee'],
    'future date' => [['spent_on' => '2999-01-01'], 'spent_on'],
    'bad date' => [['spent_on' => 'yesterday'], 'spent_on'],
    'unknown category' => [['expense_category_id' => 999999], 'expense_category_id'],
]);

test('a category from another school is rejected', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $foreign = accountsCategory(School::factory()->create(), 'Foreign');

    $this->actingAs($owner)->post(route('accounts.expenses.store'), expensePayload($foreign))
        ->assertSessionHasErrors('expense_category_id');
});

// Categories

test('owner adds a category; duplicate names in a school are rejected but allowed across schools', function (): void {
    $owner = schoolUser(UserRole::Owner);

    $this->actingAs($owner)->post(route('accounts.categories.store'), ['name' => 'Rent', 'name_bn' => 'ভাড়া'])->assertRedirect();
    $this->actingAs($owner)->post(route('accounts.categories.store'), ['name' => 'Rent'])->assertSessionHasErrors('name');

    $other = schoolUser(UserRole::Owner);
    $this->actingAs($other)->post(route('accounts.categories.store'), ['name' => 'Rent'])->assertSessionDoesntHaveErrors();

    expect(CurrentSchool::runAs($owner->school_id, fn () => ExpenseCategory::count()))->toBe(1);
});

// Void

test('void keeps the row, stores reason and is logged', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $expense = accountsExpense($owner->school, accountsCategory($owner->school), 70000, now()->toDateString());

    $this->actingAs($owner)->post(route('accounts.expenses.void', $expense), ['reason' => 'Entered twice'])->assertRedirect();

    $expense = CurrentSchool::runAs($owner->school_id, fn () => Expense::findOrFail($expense->id));
    expect($expense->isVoided())->toBeTrue()
        ->and($expense->void_reason)->toBe('Entered twice')
        ->and($expense->voided_by)->toBe($owner->id)
        ->and(Activity::where('description', 'expense_voided')->count())->toBe(1);
});

test('void requires a reason and cannot be repeated', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $expense = accountsExpense($owner->school, accountsCategory($owner->school), 70000, now()->toDateString());

    $this->actingAs($owner)->post(route('accounts.expenses.void', $expense), ['reason' => ''])->assertSessionHasErrors('reason');

    $this->actingAs($owner)->post(route('accounts.expenses.void', $expense), ['reason' => 'Mistake']);
    $this->actingAs($owner)->post(route('accounts.expenses.void', $expense), ['reason' => 'Again'])->assertSessionHasErrors('reason');
});

test('there is no route to edit or delete an expense', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $expense = accountsExpense($owner->school, accountsCategory($owner->school), 70000, now()->toDateString());

    $this->actingAs($owner)->delete('/accounts/expenses/'.$expense->id)->assertStatus(405);
    $this->actingAs($owner)->put('/accounts/expenses/'.$expense->id, ['amount' => '1'])->assertStatus(405);
});

// Tenant isolation

test('voiding another schools expense is a 404 and leaves it untouched', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $other = School::factory()->create();
    $expense = accountsExpense($other, accountsCategory($other), 1000, now()->toDateString());

    $this->actingAs($owner)->post(route('accounts.expenses.void', $expense->id), ['reason' => 'Nope'])->assertNotFound();

    expect(CurrentSchool::runAs($other->id, fn () => Expense::find($expense->id)->isVoided()))->toBeFalse();
});

test('lists, summary and cash book never include another schools data', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $other = School::factory()->create();
    $today = now()->toDateString();
    accountsExpense($other, accountsCategory($other, 'Secret'), 99900, $today, ['payee' => 'Foreign Payee']);
    accountsPayment($other, 555500, now()->toDateTimeString());

    $this->actingAs($owner)->get(route('accounts.expenses'))->assertOk()->assertDontSee('Foreign Payee');
    $this->actingAs($owner)->get(route('accounts.index'))->assertOk()->assertDontSee('Secret')->assertSee('৳০.০০');
    $this->actingAs($owner)->get(route('accounts.cash-book'))->assertOk()->assertDontSee('Foreign Payee');
});

// Summary math

test('summary is income minus non voided expenses with a category breakdown', function (): void {
    $school = School::factory()->create();
    $stationery = accountsCategory($school);
    $rent = accountsCategory($school, 'Rent', null);

    accountsPayment($school, 300000, '2026-03-05 10:00:00');
    accountsPayment($school, 100001, '2026-03-31 23:59:59');
    accountsPayment($school, 999999, '2026-04-01 00:00:00');
    accountsPayment($school, 999999, '2026-03-10 10:00:00', PaymentStatus::Failed);

    accountsExpense($school, $stationery, 10050, '2026-03-02');
    accountsExpense($school, $stationery, 20000, '2026-03-31');
    accountsExpense($school, $rent, 150000, '2026-03-15');
    accountsExpense($school, $rent, 777777, '2026-03-16', ['voided_at' => now(), 'void_reason' => 'Mistake']);
    accountsExpense($school, $rent, 888888, '2026-02-28');

    $summary = CurrentSchool::runAs($school->id, fn () => app(BuildAccountsSummary::class)->forMonth(CarbonImmutable::parse('2026-03-01')));

    expect($summary['income'])->toBe(400001)
        ->and($summary['expenses'])->toBe(180050)
        ->and($summary['net'])->toBe(219951)
        ->and($summary['categories'])->toBe([
            ['name' => 'Rent', 'total' => 150000],
            ['name' => 'স্টেশনারি', 'total' => 30050],
        ]);
});

test('net can be negative and the month picker drives the page', function (): void {
    $owner = schoolUser(UserRole::Owner);
    accountsExpense($owner->school, accountsCategory($owner->school), 5000, '2026-03-10');

    $this->actingAs($owner)->get(route('accounts.index', ['month' => '2026-03']))
        ->assertOk()->assertSee('-')->assertSee('স্টেশনারি')->assertSee('৳৫০.০০');

    $this->actingAs($owner)->get(route('accounts.index', ['month' => '২০২৬-০৩']))->assertOk()->assertSee('৳৫০.০০');
    $this->actingAs($owner)->get(route('accounts.index', ['month' => 'garbage']))->assertSessionHasErrors('month');
});

// Cash book

test('cash book lists one days receipts and live expenses with totals', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $school = $owner->school;
    $category = accountsCategory($school);

    accountsPayment($school, 250000, '2026-03-10 09:00:00');
    accountsPayment($school, 111100, '2026-03-11 09:00:00');
    accountsExpense($school, $category, 100000, '2026-03-10', ['payee' => 'Day Payee']);
    accountsExpense($school, $category, 5000, '2026-03-10', ['payee' => 'Voided Payee', 'voided_at' => now(), 'void_reason' => 'x']);
    accountsExpense($school, $category, 9000, '2026-03-11', ['payee' => 'Next Day Payee']);

    $this->actingAs($owner)->get(route('accounts.cash-book', ['date' => '2026-03-10']))
        ->assertOk()
        ->assertSee('Day Payee')
        ->assertDontSee('Voided Payee')
        ->assertDontSee('Next Day Payee')
        ->assertSee('৳২,৫০০.০০')
        ->assertSee('৳১,০০০.০০')
        ->assertSee('৳১,৫০০.০০');
});

test('cash book rejects a malformed date', function (): void {
    $this->actingAs(schoolUser(UserRole::Owner))->get(route('accounts.cash-book', ['date' => '10/03/2026']))
        ->assertSessionHasErrors('date');
});

test('expense text is escaped on output', function (): void {
    $owner = schoolUser(UserRole::Owner);
    accountsExpense($owner->school, accountsCategory($owner->school), 1000, now()->toDateString(), ['payee' => '<script>alert(1)</script>']);

    $this->actingAs($owner)->get(route('accounts.expenses'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});
