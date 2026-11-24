<?php

declare(strict_types=1);

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\Mark;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Notices\Models\Notice;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Receipt;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Subject;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Guardian;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\UserRole;
use App\Support\Helpers\BanglaHelper;
use App\Support\Tenancy\CurrentSchool;

/**
 * A parent user plus one linked child (via guardian mobile) in a school.
 *
 * @return array{parent: User, student: Student, enrollment: Enrollment, year: AcademicYear, class: SchoolClass}
 */
function portalFamily(?School $school = null, ?string $mobile = null): array
{
    static $sequence = 0;
    $mobile ??= sprintf('017%08d', ++$sequence);

    $parent = schoolUser(UserRole::Parent, $school);
    $parent->forceFill(['mobile' => $mobile])->save();

    return CurrentSchool::runAs($parent->school_id, function () use ($parent, $mobile): array {
        $year = AcademicYear::query()
            ->where('school_id', $parent->school_id)
            ->where('is_current', true)
            ->first() ?? AcademicYear::factory()->create(['school_id' => $parent->school_id, 'is_current' => true]);
        $class = SchoolClass::factory()->create(['school_id' => $parent->school_id]);
        $student = Student::factory()->create(['school_id' => $parent->school_id]);
        Guardian::factory()->create(['school_id' => $parent->school_id, 'student_id' => $student->id, 'mobile' => $mobile]);
        $enrollment = Enrollment::factory()->create([
            'school_id' => $parent->school_id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'class_id' => $class->id, 'status' => 'active',
        ]);

        return compact('parent', 'student', 'enrollment', 'year', 'class');
    });
}

test('parent sees only linked children on the portal dashboard', function (): void {
    $family = portalFamily();
    CurrentSchool::runAs($family['parent']->school_id, function () use ($family): void {
        $stranger = Student::factory()->create(['school_id' => $family['parent']->school_id, 'name_bn' => 'অচেনা শিশু']);
        Guardian::factory()->create(['school_id' => $family['parent']->school_id, 'student_id' => $stranger->id, 'mobile' => '01899999999']);
    });

    $this->actingAs($family['parent'])->get(route('portal.index'))
        ->assertOk()
        ->assertSee($family['student']->name)
        ->assertDontSee('অচেনা শিশু');
});

test('dashboard redirects a parent to the portal', function (): void {
    $family = portalFamily();

    $this->actingAs($family['parent'])->get(route('dashboard'))->assertRedirect(route('portal.index'));
});

test('a parent never gets the school finance dashboard when the portal is off', function (): void {
    config(['kcam.modules.portal' => false]);
    $family = portalFamily();
    CurrentSchool::runAs($family['parent']->school_id, function () use ($family): void {
        Invoice::factory()->create([
            'school_id' => $family['parent']->school_id, 'month' => now()->format('Y-m'),
            'total' => 123400, 'paid' => 0, 'due' => 123400,
        ]);
    });

    $this->actingAs($family['parent'])->get(route('dashboard'))
        ->assertForbidden()
        ->assertDontSee(BanglaHelper::formatTaka(123400, true));
});

test('dashboard does not redirect staff', function (): void {
    $this->actingAs(schoolUser(UserRole::Owner))->get(route('dashboard'))->assertOk();
});

test('staff roles are denied the portal', function (UserRole $role): void {
    $family = portalFamily();
    $staff = schoolUser($role, $family['parent']->school);

    $this->actingAs($staff)->get(route('portal.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('portal.children.show', $family['student']))->assertForbidden();
})->with([UserRole::Owner, UserRole::Teacher, UserRole::Accountant]);

test('guests are sent to login', function (): void {
    $this->get(route('portal.index'))->assertRedirect(route('login'));
});

test('another parent gets 404 for a child that is not theirs', function (): void {
    $family = portalFamily();
    $intruder = portalFamily($family['parent']->school, '01855555555');

    $this->actingAs($intruder['parent'])->get(route('portal.children.show', $family['student']))->assertNotFound();
});

test('a parent from another school gets 404', function (): void {
    $family = portalFamily();
    $foreign = portalFamily();

    $this->actingAs($foreign['parent'])->get(route('portal.children.show', $family['student']))->assertNotFound();
});

test('a parent without a mobile sees no children', function (): void {
    $family = portalFamily();
    $family['parent']->forceFill(['mobile' => null])->save();

    $this->actingAs($family['parent'])->get(route('portal.index'))->assertOk()->assertSee('কোনো শিশু');
    $this->actingAs($family['parent'])->get(route('portal.children.show', $family['student']))->assertNotFound();
});

test('child page shows profile, attendance summary, dues and receipts', function (): void {
    $family = portalFamily();
    $schoolId = $family['parent']->school_id;

    CurrentSchool::runAs($schoolId, function () use ($family, $schoolId): void {
        foreach (['present', 'present', 'absent'] as $i => $status) {
            AttendanceRecord::factory()->create([
                'school_id' => $schoolId, 'enrollment_id' => $family['enrollment']->id,
                'date' => now()->subDays($i + 1)->toDateString(), 'status' => $status,
            ]);
        }
        $invoice = Invoice::factory()->create([
            'school_id' => $schoolId, 'student_id' => $family['student']->id, 'academic_year_id' => $family['year']->id,
            'total' => 150000, 'paid' => 50000, 'due' => 100000, 'status' => 'partial',
        ]);
        $payment = Payment::factory()->create(['school_id' => $schoolId, 'invoice_id' => $invoice->id, 'amount' => 50000]);
        Receipt::factory()->create(['school_id' => $schoolId, 'payment_id' => $payment->id, 'serial_no' => 'REC-2026-00042']);
    });

    $this->actingAs($family['parent'])->get(route('portal.children.show', $family['student']))
        ->assertOk()
        ->assertSee($family['student']->student_code)
        ->assertSee('REC-2026-00042')
        ->assertSee('উপস্থিত / Present')
        ->assertSee('৳');
});

test('only published exam results are visible', function (): void {
    $family = portalFamily();
    $schoolId = $family['parent']->school_id;

    CurrentSchool::runAs($schoolId, function () use ($family, $schoolId): void {
        $subject = Subject::factory()->create(['school_id' => $schoolId, 'class_id' => $family['class']->id]);
        $published = Exam::factory()->create(['school_id' => $schoolId, 'academic_year_id' => $family['year']->id, 'name_bn' => 'প্রকাশিত পরীক্ষা', 'published_at' => now()]);
        $draft = Exam::factory()->create(['school_id' => $schoolId, 'academic_year_id' => $family['year']->id, 'name_bn' => 'গোপন পরীক্ষা', 'published_at' => null]);
        foreach ([$published, $draft] as $exam) {
            Mark::factory()->create(['school_id' => $schoolId, 'exam_id' => $exam->id, 'enrollment_id' => $family['enrollment']->id, 'subject_id' => $subject->id]);
        }
    });

    $this->actingAs($family['parent'])->get(route('portal.children.show', $family['student']))
        ->assertOk()->assertSee('প্রকাশিত পরীক্ষা')->assertDontSee('গোপন পরীক্ষা');
});

test('notices respect audience and class targeting', function (): void {
    $family = portalFamily();
    $schoolId = $family['parent']->school_id;

    CurrentSchool::runAs($schoolId, function () use ($family, $schoolId): void {
        $author = User::factory()->create(['school_id' => $schoolId]);
        $make = fn (array $attributes) => Notice::factory()->create($attributes + ['school_id' => $schoolId, 'created_by' => $author->id]);
        $make(['title_bn' => 'সবার নোটিশ', 'audience' => 'all']);
        $make(['title_bn' => 'অভিভাবক নোটিশ', 'audience' => 'parents']);
        $make(['title_bn' => 'নিজের ক্লাস', 'audience' => 'class', 'target_class_id' => $family['class']->id]);
        $make(['title_bn' => 'অন্য ক্লাস', 'audience' => 'class', 'target_class_id' => SchoolClass::factory()->create(['school_id' => $schoolId])->id]);
        $make(['title_bn' => 'শিক্ষক নোটিশ', 'audience' => 'teachers']);
        $make(['title_bn' => 'অপ্রেরিত নোটিশ', 'audience' => 'all', 'sent_at' => null]);
    });

    $this->actingAs($family['parent'])->get(route('portal.children.show', $family['student']))
        ->assertOk()
        ->assertSee('সবার নোটিশ')->assertSee('অভিভাবক নোটিশ')->assertSee('নিজের ক্লাস')
        ->assertDontSee('অন্য ক্লাস')->assertDontSee('শিক্ষক নোটিশ')->assertDontSee('অপ্রেরিত নোটিশ');
});

test('portal routes 404 when the module is off', function (): void {
    config(['kcam.modules.portal' => false]);
    $family = portalFamily();

    $this->actingAs($family['parent'])->get(route('portal.index'))->assertNotFound();
});

test('parent role holds view-portal and staff roles do not', function (): void {
    expect(schoolUser(UserRole::Parent)->can('view-portal'))->toBeTrue()
        ->and(schoolUser(UserRole::Owner)->can('view-portal'))->toBeFalse()
        ->and(schoolUser(UserRole::Teacher)->can('view-portal'))->toBeFalse()
        ->and(schoolUser(UserRole::Accountant)->can('view-portal'))->toBeFalse();
});
