<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Fees\Actions\CollectFeeAction;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Payments\Models\Receipt;
use App\Http\Requests\CollectFeeRequest;
use App\Http\Requests\IndexFeesRequest;
use App\Support\Enums\PaymentMethod;
use App\Support\Helpers\Search;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class FeeController extends Controller
{
    /**
     * Display fee dashboard and list of invoices with dues.
     */
    public function index(IndexFeesRequest $request): View
    {
        $filters = $request->validated();
        $status = $filters['status'] ?? 'unpaid';

        $query = Invoice::with(['student.currentEnrollment.schoolClass', 'items.feeHead']);

        if ($status === 'unpaid') {
            $query->whereIn('status', ['unpaid', 'partial']);
        } elseif ($status === 'paid') {
            $query->where('status', 'paid');
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];

            $query->whereHas('student', function ($q) use ($term): void {
                Search::contains($q, ['name', 'name_bn', 'student_code'], $term);
            });
        }

        return view('fees.index', [
            'invoices' => $query->latest()->paginate(15)->withQueryString(),
            'recentReceipts' => Receipt::with(['payment.invoice.student'])->latest()->limit(5)->get(),
            'status' => $status,
        ]);
    }

    /**
     * Collect payment for an invoice.
     */
    public function collect(CollectFeeRequest $request, Invoice $invoice, CollectFeeAction $collectAction): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $receipt = $collectAction->execute(
                invoice: $invoice,
                amount: Money::toPoisha($validated['amount']),
                method: PaymentMethod::from($validated['method']),
                collector: $request->user(),
                notes: $validated['notes'] ?? null,
                clientUuid: $validated['client_uuid'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['amount' => $exception->getMessage()]);
        }

        return back()->with('success', __('fees.flash.collected', ['serial' => $receipt->serial_no]));
    }
}
