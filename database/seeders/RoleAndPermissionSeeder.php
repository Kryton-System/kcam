<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Core permissions
        $permissions = [
            // School & Settings
            'manage-school',
            'manage-academic-years',
            'manage-classes',
            'manage-subjects',
            'manage-staff',

            // Students
            'view-students',
            'create-students',
            'edit-students',
            'delete-students',
            'export-students',
            'import-students',

            // Attendance
            'view-attendance',
            'take-attendance',
            'edit-attendance',

            // Fees & Finance
            'view-fees',
            'manage-fee-structure',
            'generate-invoices',
            'collect-fees',
            'reverse-receipts',
            'view-financial-reports',

            // Exams & Results
            'view-exams',
            'manage-exams',
            'enter-marks',
            'publish-results',

            // Notices & SMS
            'view-notices',
            'send-notices',
            'send-sms',
            'view-sms-logs',

            // Accounts
            'view-accounts',
            'manage-expenses',

            // Audit & System
            'view-audit-logs',
            'manage-subscriptions',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 1. Super Admin
        $superAdminRole = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // 2. School Owner (all school operations)
        $ownerRole = Role::firstOrCreate(['name' => UserRole::Owner->value, 'guard_name' => 'web']);
        $ownerRole->syncPermissions([
            'manage-school',
            'manage-academic-years',
            'manage-classes',
            'manage-subjects',
            'manage-staff',
            'view-students',
            'create-students',
            'edit-students',
            'delete-students',
            'export-students',
            'import-students',
            'view-attendance',
            'take-attendance',
            'edit-attendance',
            'view-fees',
            'manage-fee-structure',
            'generate-invoices',
            'collect-fees',
            'reverse-receipts',
            'view-financial-reports',
            'view-exams',
            'manage-exams',
            'enter-marks',
            'publish-results',
            'view-notices',
            'send-notices',
            'send-sms',
            'view-sms-logs',
            'view-audit-logs',
            'view-accounts',
            'manage-expenses',
        ]);

        // 3. Teacher
        $teacherRole = Role::firstOrCreate(['name' => UserRole::Teacher->value, 'guard_name' => 'web']);
        $teacherRole->syncPermissions([
            'view-students',
            'view-attendance',
            'take-attendance',
            'view-exams',
            'enter-marks',
            'view-notices',
            'send-notices',
        ]);

        // 4. Accountant
        $accountantRole = Role::firstOrCreate(['name' => UserRole::Accountant->value, 'guard_name' => 'web']);
        $accountantRole->syncPermissions([
            'view-students',
            'view-fees',
            'generate-invoices',
            'collect-fees',
            'view-financial-reports',
            'view-notices',
            'view-accounts',
            'manage-expenses',
        ]);

        // 5. Parent (read-only for their children)
        $parentRole = Role::firstOrCreate(['name' => UserRole::Parent->value, 'guard_name' => 'web']);
        $parentRole->syncPermissions([
            'view-attendance',
            'view-fees',
            'view-exams',
            'view-notices',
        ]);
    }
}
