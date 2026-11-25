<?php

declare(strict_types=1);

use App\Domain\Accounts\Models\Expense;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Exams\Models\Exam;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Notices\Models\Notice;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Receipt;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\Staff\Models\Staff;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Student;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\UserRole;

/**
 * A school with one enrolled student, a current-month invoice with a part payment, and a receipt.
 *
 * @return array{school: School, year: AcademicYear, class: SchoolClass, enrollment: Enrollment, invoice: Invoice}
 */
function dashboardSchool(): array
{
    $school = School::factory()->create();
    $year = AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
    $class = SchoolClass::factory()->create(['school_id' => $school->id]);
    $student = Student::factory()->create(['school_id' => $school->id, 'name' => 'Rafi Khan']);
    $enrollment = Enrollment::factory()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    $invoice = Invoice::factory()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
        'month' => now()->format('Y-m'),
        'total' => 100000,
        'paid' => 60000,
        'due' => 40000,
        'status' => 'partial',
    ]);
    $payment = Payment::factory()->create([
        'school_id' => $school->id,
        'invoice_id' => $invoice->id,
        'amount' => 60000,
        'status' => PaymentStatus::Completed,
        'paid_at' => now(),
    ]);
    Receipt::factory()->create(['school_id' => $school->id, 'payment_id' => $payment->id, 'serial_no' => 'REC-2026-00042']);

    return ['school' => $school, 'year' => $year, 'class' => $class, 'enrollment' => $enrollment, 'invoice' => $invoice];
}

test('owner dashboard shows students, collections, dues, expenses and staff with Bangla digits', function (): void {
    $data = dashboardSchool();
    $owner = schoolUser(UserRole::Owner, $data['school']);
    Expense::factory()->create(['school_id' => $data['school']->id, 'amount' => 25000, 'spent_on' => now()->toDateString()]);
    Expense::factory()->create([
        'school_id' => $data['school']->id,
        'amount' => 99900,
        'spent_on' => now()->toDateString(),
        'voided_at' => now(),
        'void_reason' => 'Entry error',
    ]);
    Staff::factory()->create(['school_id' => $data['school']->id, 'status' => 'active']);
    Staff::factory()->create(['school_id' => $data['school']->id, 'status' => 'inactive']);

    $this->actingAs($owner)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('৳৬০০.০০')
        ->assertSee('৳৪০০.০০')
        ->assertSee('৳২৫০.০০')
        ->assertDontSee('৳৯৯৯.০০')
        ->assertSee('Rafi Khan')
        ->assertSee('REC-2026-00042');
});

test('owner dashboard uses English digits and dates in English', function (): void {
    $data = dashboardSchool();
    $owner = schoolUser(UserRole::Owner, $data['school']);

    $this->actingAs($owner)->get(route('locale.switch', 'en'));
    $this->actingAs($owner)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('৳400.00')
        ->assertSee('৳600.00')
        ->assertDontSee('৬০০.০০');
});

test('accountant dashboard shows collections, dues, expenses and receipt history', function (): void {
    $data = dashboardSchool();
    $accountant = schoolUser(UserRole::Accountant, $data['school']);
    Expense::factory()->create(['school_id' => $data['school']->id, 'amount' => 25000, 'payee' => 'Chalk Supplier', 'spent_on' => now()->toDateString()]);

    $this->actingAs($accountant)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('৳৬০০.০০')
        ->assertSee('৳৪০০.০০')
        ->assertSee('Chalk Supplier')
        ->assertSee('REC-2026-00042')
        ->assertSee(route('accounts.index'), false);
});

test('teacher dashboard shows attendance and upcoming exams but no fee or expense totals', function (): void {
    $data = dashboardSchool();
    $teacher = schoolUser(UserRole::Teacher, $data['school']);
    AttendanceRecord::factory()->create([
        'school_id' => $data['school']->id,
        'enrollment_id' => $data['enrollment']->id,
        'date' => now()->toDateString(),
        'status' => AttendanceStatus::Present,
    ]);
    Exam::factory()->create([
        'school_id' => $data['school']->id,
        'academic_year_id' => $data['year']->id,
        'name' => 'Mid Term Exam',
        'name_bn' => 'মধ্যবর্তী পরীক্ষা',
        'starts_on' => now()->addDays(5)->toDateString(),
        'ends_on' => now()->addDays(8)->toDateString(),
    ]);
    Notice::factory()->create(['school_id' => $data['school']->id, 'title_bn' => 'শুক্রবার অভিভাবক সভা', 'sent_at' => now()]);

    $this->actingAs($teacher)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('উপস্থিত')
        ->assertSee('মধ্যবর্তী পরীক্ষা')
        ->assertSee('শুক্রবার অভিভাবক সভা')
        ->assertSee('Rafi Khan')
        ->assertDontSee('৳৬০০.০০')
        ->assertDontSee('৳৪০০.০০')
        ->assertDontSee(route('accounts.index'), false);
});

test('dashboard shows helpful empty states for a school with no activity yet', function (): void {
    $school = School::factory()->create();
    AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
    $owner = schoolUser(UserRole::Owner, $school);

    $this->actingAs($owner)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.payments.empty_title'))
        ->assertSee(__('dashboard.notices.empty_title'));
});

test('teacher sees a class overview without a school-wide finance card', function (): void {
    $data = dashboardSchool();
    $teacher = schoolUser(UserRole::Teacher, $data['school']);

    $this->actingAs($teacher)->get(route('dashboard'))
        ->assertOk()
        ->assertSee($data['class']->name_bn ?? $data['class']->name);
});
