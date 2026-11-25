<?php

declare(strict_types=1);

use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Student;
use App\Domain\Transport\Models\TransportAssignment;
use App\Domain\Transport\Models\TransportRoute;
use App\Domain\Transport\Models\TransportVehicle;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;

function transportEnrollment(School $school, string $status = 'active'): Enrollment
{
    return CurrentSchool::runAs($school->id, function () use ($school, $status): Enrollment {
        $year = AcademicYear::query()->where('is_current', true)->first()
            ?? AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
        $student = Student::factory()->create(['school_id' => $school->id]);

        return Enrollment::factory()->create([
            'school_id' => $school->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'status' => $status,
        ]);
    });
}

function transportRoute(School $school, int $capacity = 2): TransportRoute
{
    return CurrentSchool::runAs($school->id, function () use ($school, $capacity): TransportRoute {
        $vehicle = TransportVehicle::factory()->create(['school_id' => $school->id, 'capacity' => $capacity]);

        return TransportRoute::factory()->create(['school_id' => $school->id, 'vehicle_id' => $vehicle->id])->load('vehicle');
    });
}

function transportCount(School $school, string $model): int
{
    return CurrentSchool::runAs($school->id, fn () => $model::count());
}

test('owner and teacher can view transport, parent and accountant cannot', function (UserRole $role, int $status): void {
    $user = schoolUser($role);
    $route = transportRoute($user->school);

    $this->actingAs($user)->get(route('transport.index'))->assertStatus($status);
    $this->actingAs($user)->get(route('transport.routes.show', $route))->assertStatus($status);
    $this->actingAs($user)->get(route('transport.routes.print', $route))->assertStatus($status);
})->with([
    'owner' => [UserRole::Owner, 200],
    'teacher' => [UserRole::Teacher, 200],
    'accountant' => [UserRole::Accountant, 403],
    'parent' => [UserRole::Parent, 403],
]);

test('teacher cannot manage transport', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    $route = transportRoute($teacher->school);
    $enrollment = transportEnrollment($teacher->school);
    $assignment = CurrentSchool::runAs($teacher->school_id, fn () => TransportAssignment::factory()->create([
        'school_id' => $teacher->school_id, 'route_id' => $route->id, 'enrollment_id' => $enrollment->id,
    ]));

    $this->actingAs($teacher)->get(route('transport.routes.create'))->assertForbidden();
    $this->actingAs($teacher)->post(route('transport.routes.store'), ['name' => 'X'])->assertForbidden();
    $this->actingAs($teacher)->get(route('transport.routes.edit', $route))->assertForbidden();
    $this->actingAs($teacher)->put(route('transport.routes.update', $route), ['name' => 'X'])->assertForbidden();
    $this->actingAs($teacher)->get(route('transport.vehicles.create'))->assertForbidden();
    $this->actingAs($teacher)->post(route('transport.vehicles.store'), [])->assertForbidden();
    $this->actingAs($teacher)->post(route('transport.routes.assign', $route), [])->assertForbidden();
    $this->actingAs($teacher)->delete(route('transport.assignments.destroy', $assignment))->assertForbidden();
});

test('guests are redirected to login', function (): void {
    $this->get(route('transport.index'))->assertRedirect(route('login'));
});

test('owner creates a vehicle with a canonical mobile number', function (): void {
    $owner = schoolUser(UserRole::Owner);

    $this->actingAs($owner)->post(route('transport.vehicles.store'), [
        'van_number' => 'ঢাকা মেট্রো ১২-৩৪৫৬',
        'driver_name' => 'আব্দুর রহিম',
        'driver_mobile' => '+৮৮০১৭১২৩৪৫৬৭৮',
        'capacity' => '১২',
    ])->assertRedirect(route('transport.index'));

    $vehicle = CurrentSchool::runAs($owner->school_id, fn () => TransportVehicle::first());
    expect($vehicle->driver_mobile)->toBe('01712345678')
        ->and($vehicle->capacity)->toBe(12)
        ->and($vehicle->school_id)->toBe($owner->school_id);
});

test('vehicle validation rejects bad input', function (array $override, string $field): void {
    $owner = schoolUser(UserRole::Owner);

    $this->actingAs($owner)->post(route('transport.vehicles.store'), array_merge([
        'van_number' => 'V-1', 'driver_name' => 'Rahim', 'driver_mobile' => '01712345678', 'capacity' => '10',
    ], $override))->assertSessionHasErrors($field);
})->with([
    'no van number' => [['van_number' => ''], 'van_number'],
    'no driver' => [['driver_name' => ''], 'driver_name'],
    'bad mobile' => [['driver_mobile' => '12345'], 'driver_mobile'],
    'zero capacity' => [['capacity' => '0'], 'capacity'],
    'huge capacity' => [['capacity' => '1000'], 'capacity'],
    'fractional capacity' => [['capacity' => '10.5'], 'capacity'],
]);

test('van number must be unique inside a school but may repeat across schools', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $other = schoolUser(UserRole::Owner);
    CurrentSchool::runAs($owner->school_id, fn () => TransportVehicle::factory()->create(['school_id' => $owner->school_id, 'van_number' => 'V-1']));
    $payload = ['van_number' => 'V-1', 'driver_name' => 'Rahim', 'driver_mobile' => '01712345678', 'capacity' => '10'];

    $this->actingAs($owner)->post(route('transport.vehicles.store'), $payload)->assertSessionHasErrors('van_number');
    $this->actingAs($other)->post(route('transport.vehicles.store'), $payload)->assertSessionHasNoErrors();
});

test('owner updates a vehicle and cannot shrink capacity below the seats in use', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school, 3);
    foreach ([1, 2] as $ignored) {
        CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::factory()->create([
            'school_id' => $owner->school_id, 'route_id' => $route->id,
            'enrollment_id' => transportEnrollment($owner->school)->id,
        ]));
    }
    $vehicle = $route->vehicle;
    $payload = ['van_number' => $vehicle->van_number, 'driver_name' => 'Karim', 'driver_mobile' => '01812345678'];

    $this->actingAs($owner)->get(route('transport.vehicles.edit', $vehicle))->assertOk();
    $this->actingAs($owner)->put(route('transport.vehicles.update', $vehicle), $payload + ['capacity' => '1'])
        ->assertSessionHasErrors('capacity');
    $this->actingAs($owner)->put(route('transport.vehicles.update', $vehicle), $payload + ['capacity' => '2'])
        ->assertRedirect(route('transport.index'));

    expect($vehicle->fresh()->capacity)->toBe(2)->and($vehicle->fresh()->driver_name)->toBe('Karim');
});

test('owner creates a route with stops and a vehicle', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $vehicle = CurrentSchool::runAs($owner->school_id, fn () => TransportVehicle::factory()->create(['school_id' => $owner->school_id]));

    $this->actingAs($owner)->get(route('transport.routes.create'))->assertOk()->assertSee('রুট');
    $this->actingAs($owner)->post(route('transport.routes.store'), [
        'name' => 'Mirpur Line', 'name_bn' => 'মিরপুর লাইন', 'stops' => "Mirpur 10\nPallabi", 'vehicle_id' => $vehicle->id,
    ])->assertRedirect();

    $route = CurrentSchool::runAs($owner->school_id, fn () => TransportRoute::first());
    expect($route->name)->toBe('Mirpur Line')->and($route->vehicle_id)->toBe($vehicle->id)
        ->and($route->stopList())->toBe(['Mirpur 10', 'Pallabi']);
});

test('route validation: name required, vehicle must belong to the school', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $foreignVehicle = CurrentSchool::runAs(
        ($other = School::factory()->create())->id,
        fn () => TransportVehicle::factory()->create(['school_id' => $other->id]),
    );

    $this->actingAs($owner)->post(route('transport.routes.store'), ['name' => ''])->assertSessionHasErrors('name');
    $this->actingAs($owner)->post(route('transport.routes.store'), ['name' => 'R', 'vehicle_id' => $foreignVehicle->id])
        ->assertSessionHasErrors('vehicle_id');
    expect(transportCount($owner->school, TransportRoute::class))->toBe(0);
});

test('owner edits a route, but cannot move it to a vehicle too small for its riders', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school, 3);
    foreach ([1, 2] as $ignored) {
        CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::factory()->create([
            'school_id' => $owner->school_id, 'route_id' => $route->id,
            'enrollment_id' => transportEnrollment($owner->school)->id,
        ]));
    }
    $small = CurrentSchool::runAs($owner->school_id, fn () => TransportVehicle::factory()->create(['school_id' => $owner->school_id, 'capacity' => 1]));

    $this->actingAs($owner)->get(route('transport.routes.edit', $route))->assertOk();
    $this->actingAs($owner)->put(route('transport.routes.update', $route), ['name' => 'Renamed', 'vehicle_id' => $small->id])
        ->assertSessionHasErrors('vehicle_id');
    $this->actingAs($owner)->put(route('transport.routes.update', $route), ['name' => 'Renamed', 'vehicle_id' => $route->vehicle_id])
        ->assertRedirect();

    expect($route->fresh()->name)->toBe('Renamed');
});

test('owner assigns an enrolled student with the fee stored in poisha', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school);
    $enrollment = transportEnrollment($owner->school);

    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => $enrollment->id, 'stop' => 'Pallabi', 'monthly_fee' => '৮৫০.৫০',
    ])->assertRedirect(route('transport.routes.show', $route));

    $assignment = CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::first());
    expect($assignment->monthly_fee)->toBe(85050)->and($assignment->stop)->toBe('Pallabi')
        ->and($assignment->route_id)->toBe($route->id);
});

test('fee validation', function (string $fee): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school);
    $enrollment = transportEnrollment($owner->school);

    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => $enrollment->id, 'monthly_fee' => $fee,
    ])->assertSessionHasErrors('monthly_fee');
    expect(transportCount($owner->school, TransportAssignment::class))->toBe(0);
})->with(['empty' => [''], 'negative' => ['-5'], 'text' => ['abc'], 'three decimals' => ['10.123'], 'too long' => ['1234567890']]);

test('zero fee is allowed for a free ride', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school);
    $enrollment = transportEnrollment($owner->school);

    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => $enrollment->id, 'monthly_fee' => '0',
    ])->assertSessionHasNoErrors();

    expect(CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::first()->monthly_fee))->toBe(0);
});

test('assignment cannot exceed the vehicle capacity', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school, 2);
    $payload = fn (Enrollment $e): array => ['enrollment_id' => $e->id, 'monthly_fee' => '500'];

    $this->actingAs($owner)->post(route('transport.routes.assign', $route), $payload(transportEnrollment($owner->school)))->assertSessionHasNoErrors();
    $this->actingAs($owner)->post(route('transport.routes.assign', $route), $payload(transportEnrollment($owner->school)))->assertSessionHasNoErrors();
    $this->actingAs($owner)->post(route('transport.routes.assign', $route), $payload(transportEnrollment($owner->school)))
        ->assertSessionHasErrors('enrollment_id');

    expect(transportCount($owner->school, TransportAssignment::class))->toBe(2);
});

test('assignment needs a vehicle on the route', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = CurrentSchool::runAs($owner->school_id, fn () => TransportRoute::factory()->create(['school_id' => $owner->school_id]));

    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => transportEnrollment($owner->school)->id, 'monthly_fee' => '500',
    ])->assertSessionHasErrors('enrollment_id');
});

test('a student can ride only one route', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $routeA = transportRoute($owner->school);
    $routeB = transportRoute($owner->school);
    $enrollment = transportEnrollment($owner->school);
    $payload = ['enrollment_id' => $enrollment->id, 'monthly_fee' => '500'];

    $this->actingAs($owner)->post(route('transport.routes.assign', $routeA), $payload)->assertSessionHasNoErrors();
    $this->actingAs($owner)->post(route('transport.routes.assign', $routeB), $payload)->assertSessionHasErrors('enrollment_id');

    expect(transportCount($owner->school, TransportAssignment::class))->toBe(1);
});

test('only active enrollments of the current year can be assigned', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school);
    $inactive = transportEnrollment($owner->school, 'transferred');
    $oldYear = CurrentSchool::runAs($owner->school_id, function () use ($owner): Enrollment {
        $year = AcademicYear::factory()->create(['school_id' => $owner->school_id, 'is_current' => false, 'name' => '2020']);

        return Enrollment::factory()->create([
            'school_id' => $owner->school_id, 'student_id' => Student::factory()->create(['school_id' => $owner->school_id])->id,
            'academic_year_id' => $year->id, 'status' => 'active',
        ]);
    });

    foreach ([$inactive, $oldYear] as $enrollment) {
        $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
            'enrollment_id' => $enrollment->id, 'monthly_fee' => '500',
        ])->assertSessionHasErrors('enrollment_id');
    }
});

test('owner removes an assignment and frees the seat', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school, 1);
    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => transportEnrollment($owner->school)->id, 'monthly_fee' => '500',
    ]);
    $assignment = CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::first());

    $this->actingAs($owner)->delete(route('transport.assignments.destroy', $assignment))
        ->assertRedirect(route('transport.routes.show', $route));
    expect(transportCount($owner->school, TransportAssignment::class))->toBe(0);

    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => transportEnrollment($owner->school)->id, 'monthly_fee' => '500',
    ])->assertSessionHasNoErrors();
});

test('roster shows riders, seats and the monthly total', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school, 5);
    $enrollment = transportEnrollment($owner->school);
    CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::factory()->create([
        'school_id' => $owner->school_id, 'route_id' => $route->id, 'enrollment_id' => $enrollment->id, 'monthly_fee' => 80050,
    ]));
    $name = CurrentSchool::runAs($owner->school_id, fn () => $enrollment->student->name_bn ?? $enrollment->student->name);

    $this->actingAs($owner)->get(route('transport.routes.show', $route))
        ->assertOk()->assertSee($name)->assertSee('৮০০.৫০')->assertSee('১ / ৫');
    $this->actingAs($owner)->get(route('transport.index'))->assertOk()->assertSee($route->name_bn);
});

test('print roster is a standalone printable page', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school);
    $enrollment = transportEnrollment($owner->school);
    CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::factory()->create([
        'school_id' => $owner->school_id, 'route_id' => $route->id, 'enrollment_id' => $enrollment->id,
    ]));

    $this->actingAs($owner)->get(route('transport.routes.print', $route))
        ->assertOk()->assertSee('window.print', false)->assertSee($route->vehicle->van_number)
        ->assertSee($route->vehicle->driver_mobile);
});

test('output is escaped', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = CurrentSchool::runAs($owner->school_id, fn () => TransportRoute::factory()->create([
        'school_id' => $owner->school_id, 'name' => '<script>alert(1)</script>', 'name_bn' => null,
    ]));

    $this->actingAs($owner)->get(route('transport.index'))->assertDontSee('<script>alert(1)</script>', false);
    $this->actingAs($owner)->get(route('transport.routes.show', $route))->assertDontSee('<script>alert(1)</script>', false);
    $this->actingAs($owner)->get(route('transport.routes.print', $route))->assertDontSee('<script>alert(1)</script>', false);
});

test('another school gets 404 for routes, vehicles and assignments', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $foreignSchool = School::factory()->create();
    $route = transportRoute($foreignSchool);
    $assignment = CurrentSchool::runAs($foreignSchool->id, fn () => TransportAssignment::factory()->create([
        'school_id' => $foreignSchool->id, 'route_id' => $route->id, 'enrollment_id' => transportEnrollment($foreignSchool)->id,
    ]));

    $this->actingAs($owner)->get(route('transport.routes.show', $route))->assertNotFound();
    $this->actingAs($owner)->get(route('transport.routes.print', $route))->assertNotFound();
    $this->actingAs($owner)->get(route('transport.routes.edit', $route))->assertNotFound();
    $this->actingAs($owner)->put(route('transport.routes.update', $route), ['name' => 'X'])->assertNotFound();
    $this->actingAs($owner)->get(route('transport.vehicles.edit', $route->vehicle))->assertNotFound();
    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => transportEnrollment($owner->school)->id, 'monthly_fee' => '500',
    ])->assertNotFound();
    $this->actingAs($owner)->delete(route('transport.assignments.destroy', $assignment))->assertNotFound();
});

test('another schools enrollment cannot be assigned', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school);
    $foreign = transportEnrollment(School::factory()->create());

    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => $foreign->id, 'monthly_fee' => '500',
    ])->assertSessionHasErrors('enrollment_id');
});

test('index lists only this schools routes', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $mine = transportRoute($owner->school);
    $theirs = transportRoute(School::factory()->create());
    CurrentSchool::runAs($theirs->school_id, fn () => $theirs->update(['name_bn' => 'অন্য স্কুলের রুট']));

    $this->actingAs($owner)->get(route('transport.index'))->assertOk()
        ->assertSee($mine->name_bn)->assertDontSee('অন্য স্কুলের রুট');
});

test('a disabled transport module is hidden and unreachable', function (): void {
    $owner = schoolUser(UserRole::Owner);
    config(['kcam.modules.transport' => false]);

    $this->actingAs($owner)->get(route('transport.index'))->assertNotFound();
    $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertDontSee(route('transport.index'), false);

    config(['kcam.modules.transport' => true]);
    $this->actingAs($owner)->get(route('dashboard'))->assertSee(route('transport.index'), false);
});

test('teacher sees the menu entry but no manage buttons', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    transportRoute($teacher->school);

    $this->actingAs($teacher)->get(route('transport.index'))->assertOk()
        ->assertDontSee(route('transport.routes.create'), false)
        ->assertDontSee(route('transport.vehicles.create'), false);
});

function sharedVanRoute(School $school, TransportVehicle $vehicle): TransportRoute
{
    return CurrentSchool::runAs($school->id, fn (): TransportRoute => TransportRoute::factory()
        ->create(['school_id' => $school->id, 'vehicle_id' => $vehicle->id])
        ->load('vehicle'));
}

test('riders on routes that share one van count against the van capacity', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $van = CurrentSchool::runAs($owner->school_id, fn () => TransportVehicle::factory()->create(['school_id' => $owner->school_id, 'capacity' => 2]));
    $routeA = sharedVanRoute($owner->school, $van);
    $routeB = sharedVanRoute($owner->school, $van);
    $payload = fn (): array => ['enrollment_id' => transportEnrollment($owner->school)->id, 'monthly_fee' => '500'];

    $this->actingAs($owner)->post(route('transport.routes.assign', $routeA), $payload())->assertSessionHasNoErrors();
    $this->actingAs($owner)->post(route('transport.routes.assign', $routeA), $payload())->assertSessionHasNoErrors();
    $this->actingAs($owner)->post(route('transport.routes.assign', $routeB), $payload())->assertSessionHasErrors('enrollment_id');

    expect(transportCount($owner->school, TransportAssignment::class))->toBe(2);
});

test('lowering van capacity counts riders on every route of that van', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $van = CurrentSchool::runAs($owner->school_id, fn () => TransportVehicle::factory()->create(['school_id' => $owner->school_id, 'capacity' => 3]));
    foreach ([sharedVanRoute($owner->school, $van), sharedVanRoute($owner->school, $van)] as $route) {
        CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::factory()->create([
            'school_id' => $owner->school_id, 'route_id' => $route->id,
            'enrollment_id' => transportEnrollment($owner->school)->id,
        ]));
    }
    $payload = ['van_number' => $van->van_number, 'driver_name' => 'Karim', 'driver_mobile' => '01812345678'];

    $this->actingAs($owner)->put(route('transport.vehicles.update', $van), $payload + ['capacity' => '1'])
        ->assertSessionHasErrors('capacity');
    $this->actingAs($owner)->put(route('transport.vehicles.update', $van), $payload + ['capacity' => '2'])
        ->assertSessionHasNoErrors();
});

test('moving a route to another van counts the riders already on that van', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $target = CurrentSchool::runAs($owner->school_id, fn () => TransportVehicle::factory()->create(['school_id' => $owner->school_id, 'capacity' => 2]));
    $resident = sharedVanRoute($owner->school, $target);
    $mover = transportRoute($owner->school, 5);
    CurrentSchool::runAs($owner->school_id, function () use ($owner, $resident, $mover): void {
        TransportAssignment::factory()->create(['school_id' => $owner->school_id, 'route_id' => $resident->id, 'enrollment_id' => transportEnrollment($owner->school)->id]);
        foreach ([1, 2] as $ignored) {
            TransportAssignment::factory()->create(['school_id' => $owner->school_id, 'route_id' => $mover->id, 'enrollment_id' => transportEnrollment($owner->school)->id]);
        }
    });

    $this->actingAs($owner)->put(route('transport.routes.update', $mover), ['name' => 'Moved', 'vehicle_id' => $target->id])
        ->assertSessionHasErrors('vehicle_id');
    expect(CurrentSchool::runAs($owner->school_id, fn () => TransportRoute::find($mover->id)->vehicle_id))->toBe($mover->vehicle_id);
});

test('teacher sees the roster and print sheet without any fee amount', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    $route = transportRoute($teacher->school, 5);
    $enrollment = transportEnrollment($teacher->school);
    CurrentSchool::runAs($teacher->school_id, fn () => TransportAssignment::factory()->create([
        'school_id' => $teacher->school_id, 'route_id' => $route->id, 'enrollment_id' => $enrollment->id, 'monthly_fee' => 80050,
    ]));
    $name = CurrentSchool::runAs($teacher->school_id, fn () => $enrollment->student->name_bn ?? $enrollment->student->name);

    $this->actingAs($teacher)->get(route('transport.routes.show', $route))
        ->assertOk()->assertSee($name)
        ->assertDontSee('৮০০.৫০')->assertDontSee('800.50')
        ->assertDontSee('মাসিক ভাড়া')->assertDontSee('মাসিক মোট');
    $this->actingAs($teacher)->get(route('transport.routes.print', $route))
        ->assertOk()->assertSee($name)
        ->assertDontSee('৮০০.৫০')->assertDontSee('800.50')
        ->assertDontSee('মাসিক ভাড়া')->assertDontSee('Fee');
});

test('owner sees per student fees on the roster and print sheet', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school, 5);
    $enrollment = transportEnrollment($owner->school);
    CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::factory()->create([
        'school_id' => $owner->school_id, 'route_id' => $route->id, 'enrollment_id' => $enrollment->id, 'monthly_fee' => 80050,
    ]));

    $this->actingAs($owner)->get(route('transport.routes.show', $route))->assertOk()->assertSee('৮০০.৫০');
    $this->actingAs($owner)->get(route('transport.routes.print', $route))->assertOk()->assertSee('৮০০.৫০');
});

test('a monthly fee above the storable limit is a validation error', function (string $fee): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school);
    $enrollment = transportEnrollment($owner->school);

    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => $enrollment->id, 'monthly_fee' => $fee,
    ])->assertSessionHasErrors('monthly_fee');
    expect(transportCount($owner->school, TransportAssignment::class))->toBe(0);
})->with(['just above the cap' => ['40000000.01'], 'nine digits' => ['999999999']]);

test('the largest storable monthly fee is accepted and stored in poisha', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $route = transportRoute($owner->school);

    $this->actingAs($owner)->post(route('transport.routes.assign', $route), [
        'enrollment_id' => transportEnrollment($owner->school)->id, 'monthly_fee' => '40000000',
    ])->assertSessionHasNoErrors();

    expect(CurrentSchool::runAs($owner->school_id, fn () => TransportAssignment::first()->monthly_fee))->toBe(4000000000);
});
