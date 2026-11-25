<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Domain\Student\Models\Guardian;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\UserRole;

function parentWithChildren(string $mobile, array $childNames): array
{
    $school = School::factory()->create();
    $parent = User::factory()->create(['school_id' => $school->id, 'role' => UserRole::Parent, 'mobile' => $mobile]);

    $children = collect($childNames)->map(function (string $name) use ($school, $mobile): Student {
        $student = Student::factory()->create(['school_id' => $school->id, 'name' => $name]);
        Guardian::factory()->create(['school_id' => $school->id, 'student_id' => $student->id, 'mobile' => $mobile, 'is_primary' => true]);

        return $student;
    });

    return [$parent, $children];
}

test('child page lists the parent\'s other children so the parent can switch between them', function (): void {
    [$parent, $children] = parentWithChildren('01711000001', ['Rafi Khan', 'Nadia Khan']);

    $this->actingAs($parent)->get(route('portal.children.show', $children[0]))
        ->assertOk()
        ->assertSee(route('portal.children.show', $children[1]), false);
});

test('a parent with one child sees no switcher', function (): void {
    [$parent, $children] = parentWithChildren('01711000002', ['Only Child']);

    $this->actingAs($parent)->get(route('portal.children.show', $children[0]))
        ->assertOk()
        ->assertDontSee('data-child-switcher', false);
});

test('a parent cannot open a child linked to a different mobile number', function (): void {
    [$parent] = parentWithChildren('01711000003', ['Mine']);
    [, $stranger] = parentWithChildren('01822000004', ['Not Mine']);

    $this->actingAs($parent)->get(route('portal.children.show', $stranger[0]))->assertNotFound();
});
