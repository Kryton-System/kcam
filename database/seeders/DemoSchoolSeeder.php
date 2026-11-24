<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Fees\Models\FeeHead;
use App\Domain\Fees\Models\FeeStructure;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Fees\Models\InvoiceItem;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Receipt;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Section;
use App\Domain\School\Models\Subject;
use App\Domain\Staff\Models\Staff;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Guardian;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\FeeHeadType;
use App\Support\Enums\PaymentMethod;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\StudentStatus;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // The demo accounts all share a well known password: never create them on a real server.
        if (! app()->environment('local', 'testing')) {
            logger()->warning('DemoSchoolSeeder skipped: it only runs in the local and testing environments.');

            return;
        }

        CurrentSchool::runAsPlatform(fn () => $this->seed());
    }

    private function seed(): void
    {
        // 1. Create Demo School
        $school = School::create([
            'name' => 'Green Valley Kindergarten',
            'name_bn' => 'সবুজ কুঁড়ি কিন্ডারগার্টেন',
            'eiin' => '134520',
            'phone' => '01711000000',
            'email' => 'info@greenvalley.edu.bd',
            'address' => 'House 12, Road 5, Dhanmondi, Dhaka-1205',
            'address_bn' => 'বাড়ি ১২, রোড ৫, ধানমন্ডি, ঢাকা-১২০৫',
            'plan_id' => 'standard',
            'status' => 'active',
            'settings' => [
                'currency' => 'BDT',
                'sms_enabled' => true,
                'timezone' => 'Asia/Dhaka',
            ],
        ]);

        // 2. Create Users
        $owner = (new User)->forceFill([
            'school_id' => $school->id,
            'name' => 'Mohammad Azharul Islam',
            'email' => 'owner@kcam.test',
            'mobile' => '01711111111',
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $owner->save();
        $owner->assignRole(UserRole::Owner->value);

        $teacherUser = (new User)->forceFill([
            'school_id' => $school->id,
            'name' => 'Nasrin Akhter',
            'email' => 'teacher@kcam.test',
            'mobile' => '01722222222',
            'role' => UserRole::Teacher,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $teacherUser->save();
        $teacherUser->assignRole(UserRole::Teacher->value);

        $accountantUser = (new User)->forceFill([
            'school_id' => $school->id,
            'name' => 'Kamal Hossain',
            'email' => 'accountant@kcam.test',
            'mobile' => '01733333333',
            'role' => UserRole::Accountant,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $accountantUser->save();
        $accountantUser->assignRole(UserRole::Accountant->value);

        $parentUser = (new User)->forceFill([
            'school_id' => $school->id,
            'name' => 'Abdur Rahim',
            'email' => 'parent@kcam.test',
            'mobile' => '01744444444',
            'role' => UserRole::Parent,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $parentUser->save();
        $parentUser->assignRole(UserRole::Parent->value);

        // 3. Academic Year
        $academicYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);

        // 4. Classes and Sections
        $classesData = [
            ['name' => 'Play', 'name_bn' => 'প্লে', 'numeric_name' => 0, 'order' => 1],
            ['name' => 'Nursery', 'name_bn' => 'নার্সারি', 'numeric_name' => 0, 'order' => 2],
            ['name' => 'KG', 'name_bn' => 'কেজি', 'numeric_name' => 0, 'order' => 3],
            ['name' => 'Class 1', 'name_bn' => '১ম শ্রেণি', 'numeric_name' => 1, 'order' => 4],
            ['name' => 'Class 2', 'name_bn' => '২য় শ্রেণি', 'numeric_name' => 2, 'order' => 5],
            ['name' => 'Class 3', 'name_bn' => '৩য় শ্রেণি', 'numeric_name' => 3, 'order' => 6],
            ['name' => 'Class 4', 'name_bn' => '৪র্থ শ্রেণি', 'numeric_name' => 4, 'order' => 7],
            ['name' => 'Class 5', 'name_bn' => '৫ম শ্রেণি', 'numeric_name' => 5, 'order' => 8],
        ];

        $createdClasses = [];
        foreach ($classesData as $classItem) {
            $class = SchoolClass::create([
                'school_id' => $school->id,
                'name' => $classItem['name'],
                'name_bn' => $classItem['name_bn'],
                'numeric_name' => $classItem['numeric_name'],
                'order' => $classItem['order'],
            ]);

            // Add Sections: ক (A) and খ (B)
            Section::create([
                'school_id' => $school->id,
                'class_id' => $class->id,
                'name' => 'A',
                'name_bn' => 'ক',
            ]);

            Section::create([
                'school_id' => $school->id,
                'class_id' => $class->id,
                'name' => 'B',
                'name_bn' => 'খ',
            ]);

            $createdClasses[] = $class;
        }

        // 5. Subjects for Class 1
        $class1 = $createdClasses[3]; // Class 1
        $subjectsData = [
            ['name' => 'Bangla', 'name_bn' => 'বাংলা', 'code' => 'BAN-101'],
            ['name' => 'English', 'name_bn' => 'ইংরেজি', 'code' => 'ENG-102'],
            ['name' => 'Mathematics', 'name_bn' => 'গণিত', 'code' => 'MAT-103'],
            ['name' => 'General Science', 'name_bn' => 'সাধারণ বিজ্ঞান', 'code' => 'SCI-104'],
            ['name' => 'Moral Studies', 'name_bn' => 'নৈতিক শিক্ষা', 'code' => 'REL-105'],
        ];

        foreach ($subjectsData as $subj) {
            Subject::create([
                'school_id' => $school->id,
                'class_id' => $class1->id,
                'name' => $subj['name'],
                'name_bn' => $subj['name_bn'],
                'code' => $subj['code'],
                'full_marks' => 100,
                'pass_marks' => 33,
                'is_optional' => false,
            ]);
        }

        // 6. Staff
        Staff::create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'name' => 'Nasrin Akhter',
            'name_bn' => 'নাসরিন আক্তার',
            'designation' => 'Senior Assistant Teacher',
            'phone' => '01722222222',
            'joined_on' => '2024-01-01',
            'salary_basic' => 1800000,
            'status' => 'active',
        ]);

        Staff::create([
            'school_id' => $school->id,
            'user_id' => $accountantUser->id,
            'name' => 'Kamal Hossain',
            'name_bn' => 'কামাল হোসেন',
            'designation' => 'Accounts Officer',
            'phone' => '01733333333',
            'joined_on' => '2024-03-01',
            'salary_basic' => 1600000,
            'status' => 'active',
        ]);

        // 7. Fee Heads & Fee Structure
        $monthlyTuition = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Monthly Tuition',
            'name_bn' => 'মাসিক বেতন',
            'type' => FeeHeadType::Monthly,
            'is_active' => true,
        ]);

        $examFee = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Exam Fee',
            'name_bn' => 'পরীক্ষার ফি',
            'type' => FeeHeadType::OneTime,
            'is_active' => true,
        ]);

        $sessionFee = FeeHead::create([
            'school_id' => $school->id,
            'name' => 'Session Fee',
            'name_bn' => 'সেশন ফি',
            'type' => FeeHeadType::Yearly,
            'is_active' => true,
        ]);

        FeeStructure::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'class_id' => $class1->id,
            'fee_head_id' => $monthlyTuition->id,
            'amount' => 80000,
        ]);

        FeeStructure::create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'class_id' => $class1->id,
            'fee_head_id' => $examFee->id,
            'amount' => 30000,
        ]);

        // 8. Students, Guardians & Enrollments for Class 1 Section A
        $sectionA = Section::where('class_id', $class1->id)->where('name', 'A')->first();

        $studentsData = [
            ['code' => 'STU-001', 'name' => 'Arafat Rahman', 'name_bn' => 'আরাফাত রহমান', 'gender' => 'male', 'roll' => 1],
            ['code' => 'STU-002', 'name' => 'Fatima Tuz Zohra', 'name_bn' => 'ফাতিমা তুজ জোহরা', 'gender' => 'female', 'roll' => 2],
            ['code' => 'STU-003', 'name' => 'Tanjim Ahmed', 'name_bn' => 'তানজিম আহমেদ', 'gender' => 'male', 'roll' => 3],
            ['code' => 'STU-004', 'name' => 'Sumaiya Akter', 'name_bn' => 'সুমাইয়া আক্তার', 'gender' => 'female', 'roll' => 4],
            ['code' => 'STU-005', 'name' => 'Mahir Faysal', 'name_bn' => 'মাহির ফয়সাল', 'gender' => 'male', 'roll' => 5],
        ];

        foreach ($studentsData as $studentItem) {
            $student = Student::create([
                'school_id' => $school->id,
                'student_code' => $studentItem['code'],
                'name' => $studentItem['name'],
                'name_bn' => $studentItem['name_bn'],
                'dob' => '2019-05-15',
                'gender' => $studentItem['gender'],
                'birth_reg_no' => '2019269'.rand(1000000000, 9999999999),
                'blood_group' => 'B+',
                'status' => StudentStatus::Active,
            ]);

            // Guardian
            Guardian::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'name' => 'Guardian of '.$studentItem['name'],
                'name_bn' => 'অভিভাবক '.$studentItem['name_bn'],
                'relation' => 'father',
                'mobile' => $studentItem['code'] === 'STU-001' ? '01744444444' : '018'.rand(10000000, 99999999),
                'is_primary' => true,
            ]);

            // Enrollment
            $enrollment = Enrollment::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'class_id' => $class1->id,
                'section_id' => $sectionA->id,
                'roll_no' => $studentItem['roll'],
                'status' => 'active',
            ]);

            // Attendance record for today
            AttendanceRecord::create([
                'school_id' => $school->id,
                'enrollment_id' => $enrollment->id,
                'date' => now()->toDateString(),
                'status' => AttendanceStatus::Present,
                'marked_by' => $teacherUser->id,
            ]);

            // Monthly Invoice
            $invoice = Invoice::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'month' => now()->format('Y-m'),
                'total' => 80000,
                'paid' => $studentItem['roll'] <= 3 ? 80000 : 0,
                'due' => $studentItem['roll'] <= 3 ? 0 : 80000,
                'status' => $studentItem['roll'] <= 3 ? 'paid' : 'unpaid',
                'due_date' => now()->addDays(10)->toDateString(),
            ]);

            InvoiceItem::create([
                'school_id' => $school->id,
                'invoice_id' => $invoice->id,
                'fee_head_id' => $monthlyTuition->id,
                'amount' => 80000,
            ]);

            // Payment and receipt for paid students
            if ($studentItem['roll'] <= 3) {
                $payment = Payment::create([
                    'school_id' => $school->id,
                    'invoice_id' => $invoice->id,
                    'method' => PaymentMethod::Cash,
                    'amount' => 80000,
                    'status' => PaymentStatus::Completed,
                    'paid_at' => now(),
                    'collected_by' => $accountantUser->id,
                    'notes' => 'Monthly fee paid at counter',
                ]);

                Receipt::create([
                    'school_id' => $school->id,
                    'payment_id' => $payment->id,
                    'serial_no' => 'REC-2026-0000'.$studentItem['roll'],
                ]);
            }
        }
    }
}
