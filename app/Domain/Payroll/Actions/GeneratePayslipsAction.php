<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Actions;

use App\Domain\Payroll\Models\Payslip;
use App\Domain\Staff\Models\Staff;
use App\Support\Tenancy\CurrentSchool;

/**
 * Creates one payslip per active staff member for a month. Safe to run again: staff who
 * already have a payslip for the month are skipped, so edited or paid slips never change.
 */
class GeneratePayslipsAction
{
    /**
     * @param  string  $month  "YYYY-MM"
     * @return int number of payslips created by this run
     */
    public function execute(int $schoolId, string $month): int
    {
        return CurrentSchool::runAs($schoolId, function () use ($schoolId, $month): int {
            $existing = Payslip::withTrashed()->where('month', $month)->pluck('staff_id')->all();
            $created = 0;

            Staff::where('status', 'active')->whereNotIn('id', $existing)->each(
                function (Staff $staff) use ($schoolId, $month, &$created): void {
                    $slip = Payslip::firstOrCreate(
                        ['school_id' => $schoolId, 'staff_id' => $staff->id, 'month' => $month],
                        ['basic' => $staff->salary_basic, 'net' => $staff->salary_basic],
                    );
                    $created += $slip->wasRecentlyCreated ? 1 : 0;
                },
            );

            return $created;
        });
    }
}
