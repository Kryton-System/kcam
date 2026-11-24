<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Accounts\Actions\BuildAccountsSummary;
use App\Domain\Accounts\Actions\RecordExpense;
use App\Domain\Accounts\Actions\VoidExpense;
use App\Domain\Accounts\Models\Expense;
use App\Domain\Accounts\Models\ExpenseCategory;
use App\Domain\Payments\Models\Payment;
use App\Http\Requests\AccountsPeriodRequest;
use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\VoidExpenseRequest;
use App\Support\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class AccountsController extends Controller
{
    public function index(AccountsPeriodRequest $request, BuildAccountsSummary $summary): View
    {
        $month = $request->month();

        return view('accounts.index', [
            'month' => $month,
            'summary' => $summary->forMonth($month),
        ]);
    }

    public function expenses(AccountsPeriodRequest $request): View
    {
        $month = $request->month();

        $expenses = Expense::with(['category', 'recorder'])
            ->whereBetween('spent_on', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->orderByDesc('spent_on')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('accounts.expenses', [
            'month' => $month,
            'expenses' => $expenses,
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }

    public function storeExpense(StoreExpenseRequest $request, RecordExpense $record): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $record->execute($validated, Money::toPoisha($validated['amount']), $request->user());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['amount' => $exception->getMessage()]);
        }

        return back()->with('success', 'খরচ সংরক্ষিত হয়েছে। / Expense saved.');
    }

    public function voidExpense(VoidExpenseRequest $request, Expense $expense, VoidExpense $void): RedirectResponse
    {
        try {
            $void->execute($expense, $request->validated('reason'), $request->user());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['reason' => $exception->getMessage()]);
        }

        return back()->with('success', 'খরচ বাতিল করা হয়েছে। / Expense voided.');
    }

    public function storeCategory(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        ExpenseCategory::create([
            'school_id' => $request->user()->school_id,
            ...$request->validated(),
        ]);

        return back()->with('success', 'খাত যুক্ত হয়েছে। / Category added.');
    }

    public function cashBook(AccountsPeriodRequest $request): View
    {
        $date = $request->date();

        $receipts = Payment::with(['invoice.student'])
            ->where('status', PaymentStatus::Completed->value)
            ->whereBetween('paid_at', [$date->startOfDay(), $date->endOfDay()])
            ->orderBy('paid_at')->get();

        $expenses = Expense::active()->with('category')
            ->whereDate('spent_on', $date->toDateString())
            ->orderBy('id')->get();

        $totalIn = (int) $receipts->sum('amount');
        $totalOut = (int) $expenses->sum('amount');

        return view('accounts.cash-book', [
            'date' => $date,
            'receipts' => $receipts,
            'expenses' => $expenses,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'closing' => $totalIn - $totalOut,
        ]);
    }
}
