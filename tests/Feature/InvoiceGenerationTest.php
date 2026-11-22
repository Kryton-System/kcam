<?php

declare(strict_types=1);

use App\Domain\Fees\Actions\GenerateMonthlyInvoicesAction;
use App\Domain\Fees\Models\FeeDiscount;
use App\Domain\Fees\Models\FeeHead;
use App\Domain\Fees\Models\FeeStructure;
use App\Domain\Fees\Models\Invoice;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Student;
use App\Support\Enums\FeeHeadType;
use App\Support\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generate monthly invoices action creates invoices with applied discounts', function (): void {
    $school = School::factory()->create();
    $year = AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
    $class = SchoolClass::factory()->create(['school_id' => $school->id]);

    // Fee Heads: Monthly Tuition = 800 BDT, Electricity = 100 BDT
    $tuition = FeeHead::factory()->create([
        'school_id' => $school->id,
        'name' => 'Tuition Fee',
        'type' => FeeHeadType::Monthly,
    ]);
    FeeStructure::factory()->create([
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
        'class_id' => $class->id,
        'fee_head_id' => $tuition->id,
        'amount' => 80000,
    ]);

    // Student 1 (no discount)
    $student1 = Student::factory()->create(['school_id' => $school->id]);
    Enrollment::factory()->create([
        'school_id' => $school->id,
        'student_id' => $student1->id,
        'academic_year_id' => $year->id,
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    // Student 2 (with 200 BDT fixed discount on tuition)
    $student2 = Student::factory()->create(['school_id' => $school->id]);
    Enrollment::factory()->create([
        'school_id' => $school->id,
        'student_id' => $student2->id,
        'academic_year_id' => $year->id,
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    FeeDiscount::factory()->create([
        'school_id' => $school->id,
        'student_id' => $student2->id,
        'fee_head_id' => $tuition->id,
        'type' => 'fixed',
        'value' => 20000,
    ]);

    $action = app(GenerateMonthlyInvoicesAction::class);
    $generatedCount = $action->execute(
        school: $school,
        academicYear: $year,
        month: '2026-04',
        dueDate: '2026-04-10',
    );

    // Reads need a school context
    $this->actingAs(schoolUser(UserRole::Owner, $school));

    expect($generatedCount)->toBe(2);

    // Verify student 1 invoice (800 BDT total)
    $inv1 = Invoice::where('student_id', $student1->id)->where('month', '2026-04')->first();
    expect($inv1)->not->toBeNull()
        ->and($inv1->total)->toBe(80000)
        ->and($inv1->due)->toBe(80000)
        ->and($inv1->status)->toBe('unpaid');

    // Verify student 2 invoice (800 - 200 = 600 BDT total)
    $inv2 = Invoice::where('student_id', $student2->id)->where('month', '2026-04')->first();
    expect($inv2)->not->toBeNull()
        ->and($inv2->total)->toBe(60000)
        ->and($inv2->due)->toBe(60000)
        ->and($inv2->status)->toBe('unpaid');

    // Running again for same month should not duplicate invoices
    $secondRunCount = $action->execute(
        school: $school,
        academicYear: $year,
        month: '2026-04',
        dueDate: '2026-04-10',
    );
    expect($secondRunCount)->toBe(0);
});

test('percentage discounts are stored as basis points and applied in whole poisha', function (): void {
    $school = School::factory()->create();
    $year = AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
    $class = SchoolClass::factory()->create(['school_id' => $school->id]);
    $head = FeeHead::factory()->create(['school_id' => $school->id, 'type' => FeeHeadType::Monthly]);
    FeeStructure::factory()->create([
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
        'class_id' => $class->id,
        'fee_head_id' => $head->id,
        'amount' => 80000,
    ]);
    $student = Student::factory()->create(['school_id' => $school->id]);
    Enrollment::factory()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    // 12.5% is 1250 basis points
    FeeDiscount::factory()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'fee_head_id' => $head->id,
        'type' => 'percentage',
        'value' => 1250,
    ]);

    app(GenerateMonthlyInvoicesAction::class)->execute($school, $year, '2026-05');

    $this->actingAs(schoolUser(UserRole::Owner, $school));

    // 12.5% of 800.00 taka is 100.00 taka, so the invoice is 700.00 taka
    expect(Invoice::where('student_id', $student->id)->first()->total)->toBe(70000);
});
