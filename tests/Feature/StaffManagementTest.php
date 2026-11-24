<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Domain\Staff\Models\Staff;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;

if (! function_exists('makeStaff')) {
    function makeStaff(School $school, array $attributes = []): Staff
    {
        return CurrentSchool::runAs($school->id, fn (): Staff => Staff::factory()->create(array_merge(
            ['school_id' => $school->id, 'user_id' => null], $attributes,
        )));
    }
}

function staffPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rahela Begum',
        'name_bn' => 'রাহেলা বেগম',
        'designation' => 'Assistant Teacher',
        'phone' => '০১৭১১২২৩৩৪৪',
        'joined_on' => '2025-02-01',
        'salary' => '12000.50',
        'status' => 'active',
    ], $overrides);
}

describe('staff authorization', function (): void {
    test('owner can open the staff pages', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $staff = makeStaff($school);

        $this->actingAs($owner)->get('/staff')->assertOk()->assertSee('কর্মী / Staff');
        $this->actingAs($owner)->get('/staff/create')->assertOk();
        $this->actingAs($owner)->get("/staff/{$staff->id}/edit")->assertOk();
    });

    test('other roles are denied', function (UserRole $role): void {
        $school = School::factory()->create();
        $user = schoolUser($role, $school);
        $staff = makeStaff($school);

        $this->actingAs($user)->get('/staff')->assertForbidden();
        $this->actingAs($user)->get('/staff/create')->assertForbidden();
        $this->actingAs($user)->post('/staff', staffPayload())->assertForbidden();
        $this->actingAs($user)->get("/staff/{$staff->id}/edit")->assertForbidden();
        $this->actingAs($user)->put("/staff/{$staff->id}", staffPayload())->assertForbidden();
    })->with([UserRole::Teacher, UserRole::Accountant, UserRole::Parent]);

    test('guests are redirected to login', function (): void {
        $this->get('/staff')->assertRedirect('/login');
    });

    test('the module switch hides the pages', function (): void {
        $owner = schoolUser(UserRole::Owner);
        config(['kcam.modules.payroll' => false]);

        $this->actingAs($owner)->get('/staff')->assertNotFound();
        $this->actingAs($owner)->get('/dashboard')->assertOk()->assertDontSee(route('staff.index'), false);
    });

    test('the menu shows staff and payroll links to the owner', function (): void {
        $owner = schoolUser(UserRole::Owner);

        $this->actingAs($owner)->get('/dashboard')->assertOk()
            ->assertSee(route('staff.index'), false)
            ->assertSee(route('payroll.index'), false);
    });
});

describe('staff crud', function (): void {
    test('list shows only this school staff and supports search', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        makeStaff($school, ['name' => 'Alpha Teacher']);
        makeStaff($school, ['name' => 'Beta Teacher']);
        makeStaff(School::factory()->create(), ['name' => 'Foreign Teacher']);

        $this->actingAs($owner)->get('/staff')->assertSee('Alpha Teacher')->assertSee('Beta Teacher')->assertDontSee('Foreign Teacher');
        $this->actingAs($owner)->get('/staff?search=Beta')->assertSee('Beta Teacher')->assertDontSee('Alpha Teacher');
    });

    test('owner adds staff, salary is stored in integer poisha and digits are normalised', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);

        $this->actingAs($owner)->post('/staff', staffPayload(['salary' => '১২০০০.৫০']))->assertRedirect(route('staff.index'));

        $staff = CurrentSchool::runAs($school->id, fn () => Staff::firstOrFail());
        expect($staff->salary_basic)->toBeInt()->toBe(1200050)
            ->and($staff->phone)->toBe('01711223344')
            ->and($staff->school_id)->toBe($school->id)
            ->and($staff->status)->toBe('active');
    });

    test('validation rejects bad input', function (array $overrides, string $field): void {
        $owner = schoolUser(UserRole::Owner);

        $this->actingAs($owner)->post('/staff', staffPayload($overrides))->assertSessionHasErrors($field);
    })->with([
        'missing name' => [['name' => ''], 'name'],
        'missing designation' => [['designation' => ''], 'designation'],
        'bad mobile' => [['phone' => '12345'], 'phone'],
        'bad date' => [['joined_on' => 'tomorrow'], 'joined_on'],
        'three decimals' => [['salary' => '100.123'], 'salary'],
        'negative salary' => [['salary' => '-5'], 'salary'],
        'text salary' => [['salary' => 'abc'], 'salary'],
        'bad status' => [['status' => 'fired'], 'status'],
    ]);

    test('owner edits staff', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        $staff = makeStaff($school);

        $this->actingAs($owner)->put("/staff/{$staff->id}", staffPayload(['name' => 'Renamed', 'salary' => '999', 'status' => 'inactive']))
            ->assertRedirect(route('staff.index'));

        $fresh = CurrentSchool::runAs($school->id, fn () => Staff::findOrFail($staff->id));
        expect($fresh->name)->toBe('Renamed')->and($fresh->salary_basic)->toBe(99900)->and($fresh->status)->toBe('inactive');
    });

    test('mass assignment cannot move staff to another school', function (): void {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);

        $this->actingAs($owner)->post('/staff', staffPayload(['school_id' => $other->id]))->assertRedirect();

        expect(CurrentSchool::runAs($school->id, fn () => Staff::count()))->toBe(1)
            ->and(CurrentSchool::runAs($other->id, fn () => Staff::count()))->toBe(0);
    });

    test('another school staff record is a 404', function (): void {
        $owner = schoolUser(UserRole::Owner);
        $foreign = makeStaff(School::factory()->create());

        $this->actingAs($owner)->get("/staff/{$foreign->id}/edit")->assertNotFound();
        $this->actingAs($owner)->put("/staff/{$foreign->id}", staffPayload())->assertNotFound();
    });

    test('output is escaped', function (): void {
        $school = School::factory()->create();
        $owner = schoolUser(UserRole::Owner, $school);
        makeStaff($school, ['name' => '<script>alert(1)</script>', 'name_bn' => null]);

        $this->actingAs($owner)->get('/staff')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    });
});
