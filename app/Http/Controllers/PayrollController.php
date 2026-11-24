<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Payroll\Actions\GeneratePayslipsAction;
use App\Domain\Payroll\Models\Payslip;
use App\Http\Requests\GeneratePayrollRequest;
use App\Http\Requests\IndexPayrollRequest;
use App\Http\Requests\UpdatePayslipRequest;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(IndexPayrollRequest $request): View
    {
        $month = $request->validated('month') ?? now()->format('Y-m');
        $payslips = Payslip::with('staff')->where('month', $month)->get()->sortBy(fn (Payslip $p): string => $p->staff->name)->values();

        return view('payroll.index', [
            'month' => $month,
            'payslips' => $payslips,
            'totals' => [
                'net' => $payslips->sum('net'),
                'paid' => $payslips->where('status', Payslip::PAID)->sum('net'),
            ],
        ]);
    }

    public function generate(GeneratePayrollRequest $request, GeneratePayslipsAction $action): RedirectResponse
    {
        $month = $request->validated('month');
        $created = $action->execute((int) $request->user()->school_id, $month);

        return redirect()->route('payroll.index', ['month' => $month])
            ->with('success', "বেতন স্লিপ তৈরি হয়েছে: {$created}টি। / Payslips created: {$created}.");
    }

    public function show(Payslip $payslip): View
    {
        return view('payroll.show', ['payslip' => $payslip->load(['staff', 'payer']), 'school' => request()->user()->school]);
    }

    public function update(UpdatePayslipRequest $request, Payslip $payslip): RedirectResponse
    {
        $deductions = Money::toPoisha($request->validated('deductions'));
        $advances = Money::toPoisha($request->validated('advances'));

        $updated = Payslip::whereKey($payslip->id)->where('status', Payslip::PENDING)->update([
            'deductions' => $deductions,
            'advances' => $advances,
            'net' => $payslip->basic - $deductions - $advances,
            'notes' => $request->validated('notes'),
        ]);

        return redirect()->route('payroll.index', ['month' => $payslip->month])->with(
            $updated === 1 ? 'success' : 'error',
            $updated === 1 ? 'বেতন স্লিপ হালনাগাদ হয়েছে। / Payslip updated.' : 'পরিশোধিত স্লিপ বদলানো যায় না। / A paid payslip cannot be changed.',
        );
    }

    public function pay(Request $request, Payslip $payslip): RedirectResponse
    {
        $updated = Payslip::whereKey($payslip->id)->where('status', Payslip::PENDING)->update([
            'status' => Payslip::PAID,
            'paid_at' => now(),
            'paid_by' => $request->user()->id,
        ]);

        return redirect()->route('payroll.index', ['month' => $payslip->month])->with(
            $updated === 1 ? 'success' : 'error',
            $updated === 1 ? 'বেতন পরিশোধ হিসেবে চিহ্নিত হয়েছে। / Marked as paid.' : 'এই স্লিপ আগেই পরিশোধ হয়েছে। / Already paid.',
        );
    }
}
