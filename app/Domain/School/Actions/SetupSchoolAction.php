<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Fees\Models\FeeHead;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Section;
use App\Models\User;
use App\Support\Enums\FeeHeadType;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SetupSchoolAction
{
    /**
     * Setup a new school with academic year, classes, sections, and owner account.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): School
    {
        return DB::transaction(function () use ($data): School {
            // 1. Create School
            $school = School::create([
                'name' => $data['name'],
                'name_bn' => $data['name_bn'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'address_bn' => $data['address_bn'] ?? null,
                'status' => 'active',
                'plan_id' => $data['plan_id'] ?? 'starter',
                'settings' => [
                    'currency' => 'BDT',
                    'sms_enabled' => true,
                    'timezone' => 'Asia/Dhaka',
                ],
            ]);

            // 2. Create Owner User
            // school_id and role are not mass assignable, so they are set explicitly here.
            // The email is NOT marked verified: that needs a real verification step.
            $owner = new User([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'mobile' => $data['owner_mobile'] ?? $data['phone'] ?? null,
                'password' => Hash::make((string) $data['owner_password']),
            ]);
            $owner->forceFill([
                'school_id' => $school->id,
                'role' => UserRole::Owner,
            ])->save();

            if (class_exists(Role::class)) {
                $ownerRole = Role::where('name', UserRole::Owner->value)->first();
                if ($ownerRole) {
                    $owner->assignRole($ownerRole);
                }
            }

            // 3. Create Academic Year
            $yearName = (string) ($data['academic_year_name'] ?? date('Y'));
            AcademicYear::create([
                'school_id' => $school->id,
                'name' => $yearName,
                'starts_on' => $data['starts_on'] ?? "{$yearName}-01-01",
                'ends_on' => $data['ends_on'] ?? "{$yearName}-12-31",
                'is_current' => true,
            ]);

            // 4. Create Standard Kindergarten Classes (Play to Class 5)
            $standardClasses = [
                ['name' => 'Play', 'name_bn' => 'প্লে', 'numeric' => 0, 'order' => 1],
                ['name' => 'Nursery', 'name_bn' => 'নার্সারি', 'numeric' => 0, 'order' => 2],
                ['name' => 'KG', 'name_bn' => 'কেজি', 'numeric' => 0, 'order' => 3],
                ['name' => 'Class 1', 'name_bn' => '১ম শ্রেণি', 'numeric' => 1, 'order' => 4],
                ['name' => 'Class 2', 'name_bn' => '২য় শ্রেণি', 'numeric' => 2, 'order' => 5],
                ['name' => 'Class 3', 'name_bn' => '৩য় শ্রেণি', 'numeric' => 3, 'order' => 6],
                ['name' => 'Class 4', 'name_bn' => '৪র্থ শ্রেণি', 'numeric' => 4, 'order' => 7],
                ['name' => 'Class 5', 'name_bn' => '৫ম শ্রেণি', 'numeric' => 5, 'order' => 8],
            ];

            foreach ($standardClasses as $c) {
                $class = SchoolClass::create([
                    'school_id' => $school->id,
                    'name' => $c['name'],
                    'name_bn' => $c['name_bn'],
                    'numeric_name' => $c['numeric'],
                    'order' => $c['order'],
                ]);

                // Create Sections: A and B
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
            }

            // 5. Create Default Fee Heads
            FeeHead::create([
                'school_id' => $school->id,
                'name' => 'Monthly Tuition',
                'name_bn' => 'মাসিক বেতন',
                'type' => FeeHeadType::Monthly,
                'is_active' => true,
            ]);

            FeeHead::create([
                'school_id' => $school->id,
                'name' => 'Exam Fee',
                'name_bn' => 'পরীক্ষার ফি',
                'type' => FeeHeadType::OneTime,
                'is_active' => true,
            ]);

            FeeHead::create([
                'school_id' => $school->id,
                'name' => 'Session Fee',
                'name_bn' => 'সেশন ফি',
                'type' => FeeHeadType::Yearly,
                'is_active' => true,
            ]);

            return CurrentSchool::runAs($school->id, fn (): School => $school->fresh(['academicYears', 'classes', 'sections']));
        });
    }
}
