<?php

declare(strict_types=1);

namespace App\Domain\Fees\Actions;

use App\Domain\Fees\Models\FeeDiscount;
use App\Domain\Fees\Models\FeeStructure;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Fees\Models\InvoiceItem;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\Student\Models\Enrollment;
use App\Support\Enums\FeeHeadType;
use App\Support\Money;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyInvoicesAction
{
    /**
     * Generate monthly invoices for all active enrolled students in the school.
     */
    public function execute(
        School $school,
        AcademicYear $academicYear,
        string $month,
        ?string $dueDate = null,
    ): int {
        return CurrentSchool::runAs(
            $school->id,
            fn (): int => $this->generate($school, $academicYear, $month, $dueDate),
        );
    }

    private function generate(School $school, AcademicYear $academicYear, string $month, ?string $dueDate): int
    {
        return DB::transaction(function () use ($school, $academicYear, $month, $dueDate): int {
            // Find all active enrollments for this academic year
            $enrollments = Enrollment::withoutGlobalScope('school')
                ->where('school_id', $school->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('status', 'active')
                ->with(['student'])
                ->get();

            // Load fee structures for this academic year for monthly heads
            $structures = FeeStructure::withoutGlobalScope('school')
                ->where('school_id', $school->id)
                ->where('academic_year_id', $academicYear->id)
                ->whereHas('feeHead', function ($q): void {
                    $q->where('type', FeeHeadType::Monthly)->where('is_active', true);
                })
                ->get()
                ->groupBy('class_id');

            $invoicesCreated = 0;

            foreach ($enrollments as $enrollment) {
                // Check if invoice already exists for this student and month
                $exists = Invoice::withoutGlobalScope('school')
                    ->where('school_id', $school->id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('month', $month)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $classStructures = $structures->get($enrollment->class_id, collect());
                if ($classStructures->isEmpty()) {
                    continue;
                }

                // Load discounts for this student
                $discounts = FeeDiscount::withoutGlobalScope('school')
                    ->where('school_id', $school->id)
                    ->where('student_id', $enrollment->student_id)
                    ->get()
                    ->keyBy('fee_head_id');

                $invoiceTotal = 0;
                $itemsToCreate = [];

                foreach ($classStructures as $struct) {
                    $amount = (int) $struct->amount;

                    // Percentage discounts store basis points, fixed discounts store poisha.
                    if ($discount = $discounts->get($struct->fee_head_id)) {
                        $amount -= $discount->type === 'percentage'
                            ? Money::percentOf($amount, (int) $discount->value)
                            : (int) $discount->value;
                    }

                    $finalAmount = max(0, $amount);
                    $invoiceTotal += $finalAmount;

                    $itemsToCreate[] = [
                        'fee_head_id' => $struct->fee_head_id,
                        'amount' => $finalAmount,
                    ];
                }

                $invoice = Invoice::create([
                    'school_id' => $school->id,
                    'student_id' => $enrollment->student_id,
                    'academic_year_id' => $academicYear->id,
                    'month' => $month,
                    'total' => $invoiceTotal,
                    'paid' => 0,
                    'due' => $invoiceTotal,
                    'status' => 'unpaid',
                    'due_date' => $dueDate ?? date('Y-m-10', strtotime($month.'-01')),
                ]);

                foreach ($itemsToCreate as $itemData) {
                    InvoiceItem::create([
                        'school_id' => $school->id,
                        'invoice_id' => $invoice->id,
                        'fee_head_id' => $itemData['fee_head_id'],
                        'amount' => $itemData['amount'],
                    ]);
                }

                $invoicesCreated++;
            }

            activity()
                ->causedBy(auth()->user())
                ->withProperties(['school_id' => $school->id, 'month' => $month, 'created' => $invoicesCreated])
                ->log('invoices_generated');

            return $invoicesCreated;
        });
    }
}
