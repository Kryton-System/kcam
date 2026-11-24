<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Actions;

use App\Domain\Payroll\Models\Payslip;
use App\Domain\Staff\Models\Staff;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates one payslip per active staff member for a month. Safe to run again: staff who
 * already have a payslip for the month are skipped, so edited or paid slips never change.
 * The whole batch is one transaction, so a failure leaves no half-generated month behind.
 */
class GeneratePayslipsAction
{
    /**
     * @param  string  $month  "YYYY-MM"
     * @return int number of payslips created by this run
     */
    public function execute(int $schoolId, string $month): int
    {
        return CurrentSchool::runAs($schoolId, fn (): int => DB::transaction(
            fn (): int => $this->createMissingPayslips($schoolId, $month),
        ));
    }

    private function createMissingPayslips(int $schoolId, string $month): int
    {
        $existing = Payslip::withTrashed()->where('month', $month)->pluck('staff_id')->all();
        $created = 0;

        Staff::where('status', 'active')->whereNotIn('id', $existing)->each(
            function (Staff $staff) use ($schoolId, $month, &$created): void {
                try {
                    Payslip::create([
                        'school_id' => $schoolId,
                        'staff_id' => $staff->id,
                        'month' => $month,
                        'basic' => $staff->salary_basic,
                        'net' => $staff->salary_basic,
                    ]);
                    $created++;
                } catch (UniqueConstraintViolationException) {
                    // A concurrent run inserted this slip after our snapshot: skip it.
                }
            },
        );

        return $created;
    }
}
