<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Domain\Student\Models\Student;
use App\Support\Enums\StudentStatus;
use App\Support\Enums\UserRole;

test('status filter shows only active students, and inactive shows everyone else', function (): void {
    $owner = schoolUser(UserRole::Owner);
    Student::factory()->create(['school_id' => $owner->school_id, 'name' => 'Active Ali', 'status' => StudentStatus::Active]);
    Student::factory()->create(['school_id' => $owner->school_id, 'name' => 'Left Karim', 'status' => StudentStatus::Transferred]);

    $this->actingAs($owner)->get(route('students.index', ['status' => 'active']))
        ->assertOk()->assertSee('Active Ali')->assertDontSee('Left Karim');

    $this->actingAs($owner)->get(route('students.index', ['status' => 'inactive']))
        ->assertOk()->assertSee('Left Karim')->assertDontSee('Active Ali');
});

test('sort by student ID orders the list by code', function (): void {
    $owner = schoolUser(UserRole::Owner);
    Student::factory()->create(['school_id' => $owner->school_id, 'name' => 'Zed Second', 'student_code' => 'STU-00002']);
    Student::factory()->create(['school_id' => $owner->school_id, 'name' => 'Amy First', 'student_code' => 'STU-00001']);

    $html = $this->actingAs($owner)->get(route('students.index', ['sort' => 'code']))->getContent();

    expect(strpos($html, 'Amy First'))->toBeLessThan(strpos($html, 'Zed Second'));
});

test('unknown status or sort values are rejected', function (): void {
    $owner = schoolUser(UserRole::Owner);

    $this->actingAs($owner)->get(route('students.index', ['status' => 'graduated']))->assertSessionHasErrors('status');
    $this->actingAs($owner)->get(route('students.index', ['sort' => 'drop table']))->assertSessionHasErrors('sort');
});

test('search also finds a student by BDRIS number from another school only inside its own school', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $other = School::factory()->create();
    Student::factory()->create(['school_id' => $owner->school_id, 'name' => 'Mine', 'birth_reg_no' => '20190000000000777']);
    Student::factory()->create(['school_id' => $other->id, 'name' => 'Theirs', 'birth_reg_no' => '20190000000000777']);

    $this->actingAs($owner)->get(route('students.index', ['search' => '20190000000000777']))
        ->assertOk()->assertSee('Mine')->assertDontSee('Theirs');
});
