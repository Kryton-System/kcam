<?php

declare(strict_types=1);

use App\Domain\Payroll\Actions\GeneratePayslipsAction;
use App\Domain\Payroll\Models\Payslip;
use App\Domain\School\Models\School;
use App\Domain\Staff\Models\Staff;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! function_exists('makeStaff')) {
    function makeStaff(School $school, array $attributes = []): Staff
    {
        return CurrentSchool::runAs($school->id, fn (): Staff => Staff::factory()->create(array_merge(
            ['school_id' => $school->id, 'user_id' => null], $attributes,
        )));
    }
}

function makePayslip(School $school, array $attributes = []): Payslip
{
    return CurrentSchool::runAs($school->id, fn (): Payslip => Payslip::factory()->create(array_merge(
        ['school_id' => $school->id], $attributes,
    )));
}

function payslipCount(School $school): int
{
    return CurrentSchool::runAs($school->id, fn (): int => Payslip::count());
}

describe('payroll authorization', function (): void {
    test('owner can open payroll pages', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $payslip = makePayslip($school);

        $this->actingAs($owner)->get('/payroll?month=2026-01')->assertOk()->assertSee('বেতন / Payroll');
        $this->actingAs($owner)->get("/payroll/{$payslip->id}")->assertOk()->assertSee('বেতন স্লিপ / Payslip');
    });

    test('other roles are denied', function (UserRole $role): void {
        $school = School::factory()->create();
        $user = schoolUser($role, $school);
        $payslip = makePayslip($school);

        $this->actingAs($user)->get('/payroll')->assertForbidden();
        $this->actingAs($user)->post('/payroll/generate', ['month' => '2026-02'])->assertForbidden();
        $this->actingAs($user)->get("/payroll/{$payslip->id}")->assertForbidden();
        $this->actingAs($user)->put("/payroll/{$payslip->id}", ['deductions' => '1', 'advances' => '0'])->assertForbidden();
        $this->actingAs($user)->post("/payroll/{$payslip->id}/pay")->assertForbidden();
        expect(CurrentSchool::runAs($school->id, fn () => Payslip::findOrFail($payslip->id)->status))->toBe('pending');
    })->with([UserRole::Teacher, UserRole::Accountant, UserRole::Parent]);

    test('the module switch hides payroll', function (): void {
        $owner = schoolUser(UserRole::Owner);
        config(['kcam.modules.payroll' => false]);

        $this->actingAs($owner)->get('/payroll')->assertNotFound();
    });

    test('another school payslip is a 404 on every route', function (): void {
        $owner = schoolUser(UserRole::Owner);
        $foreign = makePayslip(School::factory()->create());

        $this->actingAs($owner)->get("/payroll/{$foreign->id}")->assertNotFound();
        $this->actingAs($owner)->put("/payroll/{$foreign->id}", ['deductions' => '1', 'advances' => '0'])->assertNotFound();
        $this->actingAs($owner)->post("/payroll/{$foreign->id}/pay")->assertNotFound();
    });

    test('the list never shows another school payslips', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        makePayslip($school, ['basic' => 1111100, 'net' => 1111100]);
        makePayslip(School::factory()->create(), ['basic' => 9999900, 'net' => 9999900]);

        $this->actingAs($owner)->get('/payroll?month=2026-01')->assertSee('১১,১১১')->assertDontSee('৯৯,৯৯৯');
    });
});

describe('generating a payroll run', function (): void {
    test('creates one payslip per active staff only', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        makeStaff($school, ['salary_basic' => 1500000]);
        makeStaff($school, ['salary_basic' => 1800050]);
        makeStaff($school, ['status' => 'inactive']);
        makeStaff(School::factory()->create());

        $this->actingAs($owner)->post('/payroll/generate', ['month' => '2026-03'])->assertRedirect(route('payroll.index', ['month' => '2026-03']));

        $slips = CurrentSchool::runAs($school->id, fn () => Payslip::orderBy('basic')->get());
        expect($slips)->toHaveCount(2)
            ->and($slips->pluck('net')->all())->toBe([1500000, 1800050])
            ->and($slips->pluck('status')->unique()->all())->toBe(['pending'])
            ->and($slips->every(fn (Payslip $p): bool => $p->month === '2026-03' && $p->deductions === 0 && $p->advances === 0))->toBeTrue();
    });

    test('is idempotent per staff and month and keeps edited slips untouched', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $staff = makeStaff($school, ['salary_basic' => 1000000]);

        $this->actingAs($owner)->post('/payroll/generate', ['month' => '2026-03']);
        $slip = CurrentSchool::runAs($school->id, fn () => Payslip::firstOrFail());
        $this->actingAs($owner)->put("/payroll/{$slip->id}", ['deductions' => '100', 'advances' => '50']);
        $staff->update(['salary_basic' => 2000000]);
        $this->actingAs($owner)->post('/payroll/generate', ['month' => '2026-03']);

        $fresh = CurrentSchool::runAs($school->id, fn () => Payslip::firstOrFail());
        expect(payslipCount($school))->toBe(1)
            ->and($fresh->basic)->toBe(1000000)
            ->and($fresh->net)->toBe(985000);
    });

    test('a run that fails part way leaves no payslips behind', function (): void {
        $school = School::factory()->create();
        makeStaff($school, ['salary_basic' => 1000000]);
        makeStaff($school, ['salary_basic' => 2000000]);
        $armed = true;
        $inserts = 0;
        Payslip::creating(function () use (&$armed, &$inserts): void {
            if ($armed && ++$inserts === 2) {
                throw new RuntimeException('simulated failure on the second payslip');
            }
        });

        try {
            expect(fn () => app(GeneratePayslipsAction::class)->execute($school->id, '2026-05'))
                ->toThrow(RuntimeException::class);
        } finally {
            $armed = false;
        }
        expect(payslipCount($school))->toBe(0);
    });

    test('a slip inserted by a concurrent run is skipped instead of failing the batch', function (): void {
        $school = School::factory()->create();
        $raced = makeStaff($school, ['salary_basic' => 1000000]);
        makeStaff($school, ['salary_basic' => 2000000]);
        $armed = true;
        Payslip::creating(function (Payslip $slip) use (&$armed, $school, $raced): void {
            if (! $armed) {
                return;
            }
            $armed = false;
            DB::table('payslips')->insert([
                'school_id' => $school->id, 'staff_id' => $raced->id, 'month' => '2026-06',
                'basic' => 1000000, 'deductions' => 0, 'advances' => 0, 'net' => 1000000,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        $created = app(GeneratePayslipsAction::class)->execute($school->id, '2026-06');

        expect($created)->toBe(1)->and(payslipCount($school))->toBe(2);
    });

    test('a later hire gets a payslip on a second run of the same month', function (): void {
        $school = School::factory()->create();
        makeStaff($school);
        $action = app(GeneratePayslipsAction::class);

        expect($action->execute($school->id, '2026-04'))->toBe(1);
        makeStaff($school);
        expect($action->execute($school->id, '2026-04'))->toBe(1)->and(payslipCount($school))->toBe(2);
        expect($action->execute($school->id, '2026-04'))->toBe(0);
    });

    test('different months are separate runs', function (): void {
        $school = School::factory()->create();
        makeStaff($school);
        $action = app(GeneratePayslipsAction::class);
        $action->execute($school->id, '2026-04');
        $action->execute($school->id, '2026-05');

        expect(payslipCount($school))->toBe(2);
    });

    test('invalid months are rejected', function (string $month): void {
        $owner = schoolUser(UserRole::Owner);

        $this->actingAs($owner)->post('/payroll/generate', ['month' => $month])->assertSessionHasErrors('month');
    })->with(['', 'March', '2026-13', '2026-3', '26-03', '2026-00']);

    test('bangla digit months are accepted', function (): void {
        $owner = schoolUser(UserRole::Owner);

        $this->actingAs($owner)->post('/payroll/generate', ['month' => '২০২৬-০৩'])->assertSessionHasNoErrors();
    });

    test('database refuses a duplicate payslip for the same staff and month', function (): void {
        $school = School::factory()->create();
        $slip = makePayslip($school);

        expect(fn () => makePayslip($school, ['staff_id' => $slip->staff_id, 'month' => $slip->month]))->toThrow(QueryException::class);
    });
});

describe('deductions and advances', function (): void {
    test('net pay is computed server side in poisha', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $slip = makePayslip($school, ['basic' => 1500000, 'net' => 1500000]);

        $this->actingAs($owner)->put("/payroll/{$slip->id}", ['deductions' => '250.50', 'advances' => '1000', 'net' => '99999999', 'basic' => '1'])
            ->assertSessionHasNoErrors();

        $fresh = CurrentSchool::runAs($school->id, fn () => Payslip::findOrFail($slip->id));
        expect($fresh->deductions)->toBeInt()->toBe(25050)
            ->and($fresh->advances)->toBe(100000)
            ->and($fresh->basic)->toBe(1500000)
            ->and($fresh->net)->toBe(1500000 - 25050 - 100000);
    });

    test('deductions plus advances cannot exceed the basic salary', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $slip = makePayslip($school, ['basic' => 100000, 'net' => 100000]);

        $this->actingAs($owner)->put("/payroll/{$slip->id}", ['deductions' => '600', 'advances' => '401'])->assertSessionHasErrors('deductions');
        $this->actingAs($owner)->put("/payroll/{$slip->id}", ['deductions' => '600', 'advances' => '400'])->assertSessionHasNoErrors();
        expect(CurrentSchool::runAs($school->id, fn () => Payslip::findOrFail($slip->id)->net))->toBe(0);
    });

    test('validation rejects bad amounts', function (array $input): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $slip = makePayslip($school);

        $this->actingAs($owner)->put("/payroll/{$slip->id}", $input)->assertSessionHasErrors();
    })->with([
        'negative' => [['deductions' => '-1', 'advances' => '0']],
        'three decimals' => [['deductions' => '1.234', 'advances' => '0']],
        'text' => [['deductions' => 'abc', 'advances' => '0']],
        'missing' => [[]],
        'note too long' => [['deductions' => '0', 'advances' => '0', 'notes' => str_repeat('x', 300)]],
    ]);

    test('a paid payslip is locked', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $slip = makePayslip($school, ['status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($owner)->put("/payroll/{$slip->id}", ['deductions' => '10', 'advances' => '0'])->assertSessionHasErrors('deductions');
        expect(CurrentSchool::runAs($school->id, fn () => Payslip::findOrFail($slip->id)->deductions))->toBe(0);
    });
});

describe('marking paid', function (): void {
    test('marks paid once and records who and when', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $slip = makePayslip($school);

        $this->actingAs($owner)->post("/payroll/{$slip->id}/pay")->assertSessionHas('success');

        $fresh = CurrentSchool::runAs($school->id, fn () => Payslip::findOrFail($slip->id));
        expect($fresh->status)->toBe('paid')->and($fresh->paid_at)->not->toBeNull()->and($fresh->paid_by)->toBe($owner->id);
    });

    test('paying twice does not change the original payment record', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $slip = makePayslip($school);

        $this->actingAs($owner)->post("/payroll/{$slip->id}/pay");
        $firstPaidAt = CurrentSchool::runAs($school->id, fn () => Payslip::findOrFail($slip->id)->paid_at);
        $this->travel(2)->hours();
        $this->actingAs($owner)->post("/payroll/{$slip->id}/pay")->assertSessionHas('error');

        expect(CurrentSchool::runAs($school->id, fn () => Payslip::findOrFail($slip->id)->paid_at->equalTo($firstPaidAt)))->toBeTrue();
    });
});

describe('printable payslip', function (): void {
    test('shows staff, month and the money breakdown', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $slip = makePayslip($school, ['month' => '2026-02', 'basic' => 1500000, 'deductions' => 25050, 'advances' => 100000, 'net' => 1374950]);

        $this->actingAs($owner)->get("/payroll/{$slip->id}")->assertOk()
            ->assertSee($slip->staff->name_bn)
            ->assertSee('2026-02')
            ->assertSee('১৫,০০০')
            ->assertSee('১৩,৭৪৯');
    });

    test('names are escaped', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $staff = makeStaff($school, ['name' => '<b>x</b>', 'name_bn' => null]);
        $slip = makePayslip($school, ['staff_id' => $staff->id]);

        $this->actingAs($owner)->get("/payroll/{$slip->id}")->assertSee('&lt;b&gt;x&lt;/b&gt;', false)->assertDontSee('<b>x</b>', false);
    });
});

describe('payroll money integrity', function (): void {
    test('payslip money columns are stored as integer types', function (string $column): void {
        expect(Schema::getColumnType('payslips', $column))->toContain('int');
    })->with(['basic', 'deductions', 'advances', 'net']);

    test('payslip amounts come back as integers', function (): void {
        $school = School::factory()->create();
        $slip = makePayslip($school, ['basic' => 123456, 'net' => 123456]);

        $fresh = CurrentSchool::runAs($school->id, fn () => Payslip::findOrFail($slip->id));
        expect($fresh->basic)->toBeInt()->and($fresh->net)->toBeInt()->and($fresh->deductions)->toBeInt()->and($fresh->advances)->toBeInt();
    });

    test('payslips cannot be queried without a school context', function (): void {
        makePayslip(School::factory()->create());
        CurrentSchool::flush();

        expect(fn () => Payslip::count())->toThrow(Exception::class);
    });

    test('staff with payslips cannot be hard deleted', function (): void {
        $slip = makePayslip(School::factory()->create());

        expect(fn () => DB::table('staff')->where('id', $slip->staff_id)->delete())->toThrow(QueryException::class);
    });

    test('net for large amounts has no float rounding', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $slip = makePayslip($school, ['basic' => 99999999999, 'net' => 99999999999]);

        $this->actingAs($owner)->put("/payroll/{$slip->id}", ['deductions' => '0.07', 'advances' => '0.01'])->assertSessionHasNoErrors();

        expect(CurrentSchool::runAs($school->id, fn () => Payslip::findOrFail($slip->id)->net))->toBe(99999999999 - 8);
    });
});
