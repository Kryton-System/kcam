# Fees and payments

This page covers how KCAM models school fees (fee heads, fee structures, monthly invoices and discounts), how staff collect a payment against an invoice, and how receipts are numbered and shown. It describes the code as it is in the working tree, not the planning documents. All money is stored as whole poisha (1 taka = 100 poisha) and shown with `LocaleFormat::taka`. For the income and cash side, see [Accounts](accounts.md).

## Status at a glance

| Area | Status in code |
|---|---|
| Fee heads | Data model and default heads from school setup. No screen to manage them. |
| Fee structures (amount per head per class) | Data model only. No screen. Created by seeders and tests. |
| Monthly invoice generation | Action only. No route, no console command, no scheduler. |
| Collecting a payment | Working. Staff screen on Fees and dues. |
| Receipts | Numbered and stored. Listed on screens. No print view or PDF. |
| Discounts | Applied during monthly generation. No screen to create them. |
| Refunds, voids, reversals | Not supported. |
| Online payment gateway | Not connected. `gateway_txn_id` is never filled from the UI. |

## Fee heads

`App\Support\Enums\FeeHeadType` (stored in `fee_heads.type`):

| Case | Stored value | English label | Invoiced by code |
|---|---|---|---|
| `Monthly` | `monthly` | Monthly | Yes, `GenerateMonthlyInvoicesAction` |
| `OneTime` | `one_time` | One-time | No code path |
| `Yearly` | `yearly` | Yearly | No code path |

`fee_heads` columns: `id`, `school_id`, `name` (max 100), `name_bn` (max 100, nullable), `type` (default `monthly`), `is_active` (default true), timestamps. Unique on `(school_id, name)`.

Setup creates three active heads when a school registers (`SetupSchoolAction`): Monthly Tuition (monthly), Exam Fee (one_time) and Session Fee (yearly). The demo seeder creates the same heads. `is_active` is only checked by monthly generation.

## Fee structures

`fee_structures` columns: `school_id`, `academic_year_id`, `class_id`, `fee_head_id`, `amount` (poisha), timestamps. Unique on `(academic_year_id, class_id, fee_head_id)`.

A structure is the amount of one fee head for one class in one academic year. There is no screen that creates or edits structures, and the `manage-fee-structure` permission is not used by any route. On a fresh install, class fees can only be set by code or the demo seeder.

## Monthly invoices

Entry point: `App\Domain\Fees\Actions\GenerateMonthlyInvoicesAction::execute(School $school, AcademicYear $academicYear, string $month, ?string $dueDate = null): int`. It returns the number of invoices created. The only callers are in tests.

```php
app(GenerateMonthlyInvoicesAction::class)->execute($school, $academicYear, '2026-01');
```

For each active enrollment in the academic year:

1. Skip the student if an invoice already exists for the same school, student and `month` string.
2. Skip the student if the student's class has no monthly fee structure in that academic year.
3. For each monthly structure, take the amount minus any matching discount (see Discounts). The result is floored at 0.
4. Create the invoice with `total` = sum of items, `paid` = 0, `due` = total, `status` = `unpaid`, and `due_date` = the given date or the 10th of the month (`YYYY-MM-10`).
5. Create one invoice item per structure with the final amount.
6. Write an `invoices_generated` activity entry with the month and the count.

The whole run is one database transaction. If one step fails, no invoices from that run are kept. Generating again for the same month skips students who already have that month, so it is safe to re-run.

## Invoices

`invoices` columns: `id`, `school_id`, `student_id`, `academic_year_id`, `month` (string, `YYYY-MM` by convention, not validated), `total`, `paid`, `due` (poisha), `status`, `due_date` (nullable), timestamps, `deleted_at` (soft delete). Unique on `(school_id, student_id, academic_year_id, month)`.

| Status | Set by | Meaning |
|---|---|---|
| `unpaid` | Generation | Nothing paid yet |
| `partial` | `CollectFeeAction` | Some paid and `due` is above 0 |
| `paid` | `CollectFeeAction` | `due` is 0 |
| `cancelled` | Nothing | Checked by `CollectFeeRequest` and the Fees screen, but no code sets it |

`invoice_items` columns: `school_id`, `invoice_id`, `fee_head_id`, `amount` (poisha), timestamps. Items have no paid amount. Payments are recorded against the invoice total only, so the item breakdown is not updated by collections.

## Discounts

`fee_discounts` columns: `school_id`, `student_id`, `fee_head_id` (nullable), `type` (string, default `fixed`), `value` (unsigned big integer, poisha or basis points), `reason` (nullable), timestamps. Indexed on `(school_id, student_id)`. There is no unique key.

Rule used by `GenerateMonthlyInvoicesAction`:

| `type` | Meaning of `value` | Discount taken off the structure amount |
|---|---|---|
| `percentage` | Basis points (1000 = 10%, 1250 = 12.5%) | `Money::percentOf`: amount times value divided by 10000, rounded half up to a whole poisha |
| Any other value (`fixed` is the default) | Poisha | `value` as is |

Final amount = max(0, structure amount minus discount).

Example: a structure of 80000 poisha (800.00 taka) with a percentage discount of 1000 gives a discount of 8000 and a final amount of 72000 (720.00 taka). A fixed discount of 20000 gives 60000 (600.00 taka).

Rules to know:

- A discount matches on student and fee head. A discount with a null `fee_head_id` never matches, so it has no effect.
- Discounts are keyed by fee head. If a student has two discounts for the same head, the one loaded last wins.
- Discounts apply only when an invoice is generated. Changing a discount later does not change an existing invoice.
- `type` is not validated. Any value other than `percentage` is treated as a poisha amount.
- Discounts only affect monthly heads, because only monthly structures are loaded.

## Collecting a payment

Screen: Fees and dues (`/fees`). Each invoice row with a due above 0 (and not cancelled) has its own form. The form posts to `fees.collect` with these fields:

| Field | Input | Rules |
|---|---|---|
| `amount` | Text, prefilled with the due in taka (for example `500.00`) | Required. Up to 9 whole digits and up to 2 decimals. At least 1 taka (100 poisha). Not more than the invoice due. Invoice must not be `cancelled`. Bangla digits are converted first. |
| `method` | Select, all `PaymentMethod` cases | Required. Must be a valid `PaymentMethod` value. |
| `client_uuid` | Hidden | Optional UUID. Generated when the page renders. |
| `notes` | Not on the form | Optional, max 255. Accepted by the request but the screen never sends it. |

Validation messages appear on the amount field, for example "The minimum payment is 1 taka." and "Payment amount exceeds remaining invoice due amount."

`CollectFeeAction::execute` steps, all in one database transaction:

1. Reject an amount of 0 or less.
2. Lock the invoice row (`lockForUpdate`), which serialises concurrent collections.
3. If `client_uuid` matches an existing payment in the school, return that payment's receipt and collect nothing.
4. Reject an amount above the locked invoice due.
5. Create a `payments` row with status `completed`, `paid_at` = now, `collected_by` = the signed-in user.
6. Take the next receipt number and create the `receipts` row.
7. Set `paid` += amount, `due` = max(0, total minus paid), and `status` = `paid` when due is 0, otherwise `partial`.
8. Write a `fee_collected` activity entry.

Partial payments are allowed: each one is a separate payment and receipt. Overpayment is rejected, and there is no credit balance.

Success message (`fees.flash.collected`): "Fee collected successfully. Receipt no: REC-2026-00001" (the number is shown in the flash message).

## Duplicate prevention

`payments.client_uuid` is unique per school. Repeating a request with the same UUID (double click, network retry) returns the first receipt and creates no second payment.

Limits:

- The UUID is generated each time the page renders. Reloading the page gives a new UUID, so a reload followed by a submit is a new attempt.
- `CollectFeeRequest` validates the amount before the action runs. If the invoice is already fully paid when a retry arrives, the request fails with the due-amount message and the original receipt is not shown. A retry after a partial payment, where the amount still fits the due, returns the original receipt.
- Two requests that arrive at the same moment are serialised by the invoice row lock. The second one finds the stored UUID.

## Payment methods

`App\Support\Enums\PaymentMethod` (stored in `payments.method`, English labels from `lang/en/enums.php`):

| Stored value | English label |
|---|---|
| `cash` | Cash |
| `bkash` | bKash |
| `nagad` | Nagad |
| `rocket` | Rocket |
| `card` | Card |
| `bank_transfer` | Bank transfer |

Bangla labels are in `lang/bn/enums.php`. The method is recorded only. Nothing reconciles it against a bank or wallet.

## Payment status

`App\Support\Enums\PaymentStatus` has `pending`, `completed`, `failed`, `refunded` and `cancelled`. Only `completed` is written, and only by `CollectFeeAction`. Invoice `paid` and the accounts income both count only completed rows. The other four values are never written anywhere in the code.

## Receipts

Receipt number format: `REC-YYYY-NNNNN`, for example `REC-2026-00001`.

- `YYYY` is the calendar year at the time of collection (PHP `date('Y')`).
- `NNNNN` is a counter per school and year, from `receipt_sequences(school_id, year, next_no)`. It starts at 1 and is incremented under a row lock inside the payment transaction, so a rolled back payment does not leave a gap.
- `receipts.serial_no` is unique per school.

`receipts` columns: `school_id`, `payment_id`, `serial_no` (max 50), `pdf_path` (nullable), timestamps. Nothing writes `pdf_path`.

Where a receipt is shown:

- The flash message after a collection (receipt number only).
- Fees and dues: the five most recent receipts (number, student, amount).
- Dashboard: recent receipts (see [Dashboards](dashboards.md)).
- Parent portal: the child's receipts (number and amount) (see [Portal](portal.md)).

There is no receipt page, no print layout and no PDF. Any print redesign would have to be built from scratch.

## Voids, reversals and refunds

Not supported. There is no route or action for them. The `reverse-receipts` permission is granted to the owner and super admin in `RoleAndPermissionSeeder`, but no route uses it. A mistaken payment cannot be corrected from the UI.

## Money format

- Storage: integers in poisha for `invoices`, `invoice_items`, `payments`, `fee_structures`, `fee_discounts` and `expenses`.
- Parsing input: `App\Support\Money::toPoisha("500.50")` returns 50050. It accepts up to 9 whole digits and up to 2 decimals.
- Formatting input back to text: `Money::toTaka(50050)` returns `500.50`.
- Display: `App\Support\Helpers\LocaleFormat::taka($amount, true)` returns the taka sign, grouped digits and 2 decimals, for example `৳1,234.50`. In the Bangla locale, digits are Bangla.

## Routes and permissions

| Method | URI | Name | Middleware | Purpose |
|---|---|---|---|---|
| GET | `/fees` | `fees.index` | `module:fees`, `can:collect-fees` | Fees and dues list, recent receipts |
| POST | `/fees/{invoice}/collect` | `fees.collect` | `module:fees`, `can:collect-fees` | Collect a payment |

The module switch is `config('kcam.modules.fees')` (environment variable `KCAM_MODULE_FEES`). When it is off, these routes return 404.

Only `collect-fees` gates a route. The other fee permissions in `RoleAndPermissionSeeder` (`view-fees`, `manage-fee-structure`, `generate-invoices`, `reverse-receipts`, `view-financial-reports`) have no route, controller check or policy in the code.

| Role | `collect-fees` | Notes |
|---|---|---|
| `super_admin` | Yes | All permissions except `view-portal` |
| `owner` | Yes | Also `generate-invoices`, `reverse-receipts` |
| `accountant` | Yes | Also `generate-invoices`, `view-fees` |
| `teacher` | No | |
| `parent` | No | Receipts only through the portal |

See [Roles and permissions](roles-and-permissions.md) for the full matrix.

## Activity log

Both entries use `spatie/laravel-activitylog`. No screen reads them.

| Event | Subject | Properties |
|---|---|---|
| `fee_collected` | Payment | `invoice_id`, `amount_poisha`, `method`, `receipt` |
| `invoices_generated` | None | `school_id`, `month`, `created` |

## Testing

Relevant feature tests: `tests/Feature/FeeCollectionTest.php`, `tests/Feature/InvoiceGenerationTest.php`, `tests/Feature/MoneyIntegrityTest.php`.

```bash
php artisan test tests/Feature/FeeCollectionTest.php tests/Feature/InvoiceGenerationTest.php tests/Feature/MoneyIntegrityTest.php
```

See [Testing](testing.md) for the wider test setup.

## Limitations

- No screens for fee heads, fee structures, discounts or invoice generation. `manage-fee-structure` and `generate-invoices` have no routes.
- Monthly invoices are never generated automatically. There is no command or scheduler.
- Only monthly heads are invoiced. One-time and yearly heads have no billing path.
- The duplicate check for a month ignores `academic_year_id`. A student with an invoice for the same month string in another academic year is skipped silently.
- A zero-total invoice (all discounts applied) is created with status `unpaid` and due 0. The row shows a Paid badge but still appears under the Unpaid filter.
- Discounts: no UI, no uniqueness per student and head, `type` not validated, and a null `fee_head_id` never applies.
- Invoices are snapshots. Later changes to structures or discounts do not update them.
- `cancelled` is checked but cannot be set. `Invoice::recalculate()` exists but nothing calls it.
- A balance under 1 taka cannot be collected from the form because of the 1 taka minimum. There is no write-off or adjustment.
- A retry after a full payment shows the due-amount error instead of the original receipt (see Duplicate prevention).
- The Fees screen does not send `notes`.
- Payments apply to the invoice total, not to individual fee items.
- Only `completed` payments exist. No refunds, voids or reversals. `reverse-receipts` is unused.
- No payment gateway. `gateway_txn_id` and the gateway parameter of `CollectFeeAction` are never set by the UI.
- Receipts have no print view or PDF. `pdf_path` is never written. The planning docs mention a receipt print redesign, but no such code exists to review.
- `due_date` is stored but not shown on screen and not used for overdue or late-fee logic.
- Validation and exception messages in `CollectFeeRequest` and `CollectFeeAction` are hard-coded English, not translation keys. See [Localization](localization.md).
