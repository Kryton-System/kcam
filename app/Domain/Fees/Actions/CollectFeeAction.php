<?php

declare(strict_types=1);

namespace App\Domain\Fees\Actions;

use App\Domain\Fees\Models\Invoice;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Receipt;
use App\Models\User;
use App\Support\Enums\PaymentMethod;
use App\Support\Enums\PaymentStatus;
use App\Support\Money;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CollectFeeAction
{
    /**
     * Collect a fee payment against an invoice and generate a receipt.
     *
     * Money is whole poisha. When a client UUID is given the call is idempotent: repeating it
     * (double click, retry, offline sync) returns the first receipt and collects nothing more.
     *
     * @throws InvalidArgumentException
     */
    public function execute(
        Invoice $invoice,
        int $amount,
        PaymentMethod $method,
        ?User $collector = null,
        ?string $notes = null,
        ?string $gatewayTxnId = null,
        ?string $clientUuid = null,
    ): Receipt {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return CurrentSchool::runAs(
            (int) $invoice->school_id,
            fn (): Receipt => $this->collect($invoice, $amount, $method, $collector, $notes, $gatewayTxnId, $clientUuid),
        );
    }

    private function collect(
        Invoice $invoice,
        int $amount,
        PaymentMethod $method,
        ?User $collector,
        ?string $notes,
        ?string $gatewayTxnId,
        ?string $clientUuid,
    ): Receipt {
        return DB::transaction(function () use ($invoice, $amount, $method, $collector, $notes, $gatewayTxnId, $clientUuid): Receipt {
            // The row lock serialises concurrent collections on the same invoice.
            $lockedInvoice = Invoice::withoutGlobalScope('school')
                ->where('id', $invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($clientUuid !== null) {
                $existing = Payment::where('school_id', $lockedInvoice->school_id)
                    ->where('client_uuid', $clientUuid)
                    ->first();

                if ($existing !== null) {
                    return Receipt::where('payment_id', $existing->id)->firstOrFail();
                }
            }

            if ($amount > (int) $lockedInvoice->due) {
                throw new InvalidArgumentException('Payment amount exceeds remaining invoice due amount.');
            }

            $payment = Payment::create([
                'school_id' => $lockedInvoice->school_id,
                'invoice_id' => $lockedInvoice->id,
                'method' => $method,
                'amount' => $amount,
                'gateway_txn_id' => $gatewayTxnId,
                'client_uuid' => $clientUuid,
                'status' => PaymentStatus::Completed,
                'paid_at' => now(),
                'collected_by' => $collector?->id ?? auth()->id(),
                'notes' => $notes,
            ]);

            $serialNo = $this->nextSerial((int) $lockedInvoice->school_id);

            $receipt = Receipt::create([
                'school_id' => $lockedInvoice->school_id,
                'payment_id' => $payment->id,
                'serial_no' => $serialNo,
            ]);

            $newPaid = (int) $lockedInvoice->paid + $amount;
            $newDue = max(0, (int) $lockedInvoice->total - $newPaid);

            $lockedInvoice->update([
                'paid' => $newPaid,
                'due' => $newDue,
                'status' => $newDue === 0 ? 'paid' : 'partial',
            ]);

            activity()
                ->performedOn($payment)
                ->causedBy($collector ?? auth()->user())
                ->withProperties([
                    'invoice_id' => $lockedInvoice->id,
                    'amount_poisha' => $amount,
                    'method' => $method->value,
                    'receipt' => $serialNo,
                ])
                ->log('fee_collected');

            return $receipt->fresh(['payment']);
        });
    }

    /**
     * Take the next receipt number for the school and year from a locked counter row, inside
     * the payment transaction: a rolled back payment also rolls back its number, so serials
     * stay gapless. Never derive the number by counting receipts.
     */
    private function nextSerial(int $schoolId): string
    {
        $year = (int) date('Y');
        $sequence = fn () => DB::table('receipt_sequences')->where('school_id', $schoolId)->where('year', $year);

        DB::table('receipt_sequences')->insertOrIgnore(['school_id' => $schoolId, 'year' => $year, 'next_no' => 1]);

        $number = (int) $sequence()->lockForUpdate()->value('next_no');
        $sequence()->update(['next_no' => $number + 1]);

        return sprintf('REC-%d-%05d', $year, $number);
    }
}
