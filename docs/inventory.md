# Inventory

This page covers the school supplies stock module: stock items, an append-only movement ledger, and the rules that keep stock from going negative. Stock on hand is never stored. It is always the sum of the ledger for an item. The state described here was checked against the working tree on 2026-11-26.

## Code map

| Concern | File |
|---|---|
| Item model | `app/Domain/Inventory/Models/InventoryItem.php` |
| Ledger model | `app/Domain/Inventory/Models/StockMovement.php` |
| Movement types | `app/Support/Enums/StockMovementType.php` |
| Recording a movement | `app/Domain/Inventory/Actions/RecordStockMovementAction.php` |
| Overdraw exception | `app/Domain/Inventory/Actions/InsufficientStockException.php` |
| Screens | `app/Http/Controllers/InventoryController.php` |
| Validation | `app/Http/Requests/StoreInventoryItemRequest.php`, `app/Http/Requests/StoreStockMovementRequest.php` |
| Messages | `lang/en/inventory.php`, `lang/bn/inventory.php` |
| Views | `resources/views/inventory/index.blade.php`, `resources/views/inventory/show.blade.php` |
| Factories | `database/factories/InventoryItemFactory.php`, `database/factories/StockMovementFactory.php` |
| Tests | `tests/Feature/InventoryTest.php` |

The code has no category model, table or field. Items are not grouped.

## Access and module switch

| Action | Route name | Permission |
|---|---|---|
| List items, add item | `inventory.index` (GET `/inventory`), `inventory.store` (POST `/inventory`) | `manage-inventory` |
| Item ledger and stock | `inventory.show` (GET `/inventory/{item}`) | `manage-inventory` |
| Record a movement | `inventory.movements.store` (POST `/inventory/{item}/movements`) | `manage-inventory` |

- Granted to owner, accountant and super_admin in `database/seeders/RoleAndPermissionSeeder.php`. Teacher and parent do not have it.
- All routes sit behind `module:inventory`. The switch is `modules.inventory` in `config/kcam.php`, set by `KCAM_MODULE_INVENTORY`. When off, the routes return 404 and the menu link disappears.
- Items are scoped to the current school. Another school's item returns 404.

See [Roles and permissions](roles-and-permissions.md) for the full role matrix.

## Data model

Table `inventory_items`:

| Column | Type | Rule | Notes |
|---|---|---|---|
| `school_id` | FK to `schools`, cascade | set by the tenancy trait | |
| `name` | string | required, max 255, unique per school | Database unique on `(school_id, name)` and a validation rule. |
| `unit` | string(30) | required, max 30 | Free text, for example pcs, kg or box. |
| `low_stock_threshold` | unsigned integer, default 0 | required integer, 0 to 1,000,000 | Digits normalised first. |

Table `stock_movements`:

| Column | Type | Notes |
|---|---|---|
| `school_id` | FK to `schools`, cascade | |
| `inventory_item_id` | FK to `inventory_items`, cascade | |
| `type` | string(20) | Cast to `StockMovementType`: `in`, `out` or `adjustment`. |
| `quantity` | signed integer | Signed delta. Positive adds stock, negative removes it. |
| `reason` | string | Required in the form, max 255. |
| `note` | text, nullable | Optional, max 2000 in the form. |
| `user_id` | FK to `users`, nullable, set null on delete | The user who recorded the movement. |
| `created_at`, `updated_at` | timestamps | Index on `(inventory_item_id, created_at)`. |

There is no soft delete on either table.

### Movement types

`App\Support\Enums\StockMovementType`:

| Case | Value | Stored sign | Form input |
|---|---|---|---|
| `In` | `in` | positive | positive integer, 1 to 1,000,000 |
| `Out` | `out` | negative (`-quantity` is stored) | positive integer, 1 to 1,000,000 |
| `Adjustment` | `adjustment` | as entered | any non-zero integer from -1,000,000 to 1,000,000 |

The labels come from `StockMovementType::label()` and are hard-coded bilingual strings, not translation keys.

## Recording a movement

`StoreStockMovementRequest` validates the form: `type` is required and must be a valid enum value, `reason` is required, and `note` is optional. The quantity rule depends on the type. `in` and `out` require a positive integer, while `adjustment` allows a signed non-zero integer.

`RecordStockMovementAction::execute` then does the following:

1. Rejects a zero quantity, and a negative quantity for `in` or `out`, with an `InvalidArgumentException`. The form already rejects these, so only code callers reach this path.
2. Converts the input to a signed delta. `Out` becomes `-quantity`. `In` and `Adjustment` keep the sign given.
3. Opens a transaction and locks the item row with `lockForUpdate`, so two removals cannot both pass the stock check.
4. Sums the item's ledger to get the available stock.
5. Throws `InsufficientStockException` when `available + delta < 0`.
6. Inserts one `stock_movements` row with `school_id`, `inventory_item_id`, `type`, `quantity` (the delta), `reason`, `note` and `user_id`.

The controller maps the exception to a validation error on `quantity`, using `inventory.flash.insufficient_stock`. The flash for success is "Movement recorded." (`inventory.flash.movement_recorded`).

## How stock is computed

- There is no stored balance column. `InventoryItem::currentStock()` returns the sum of `quantity` over the item's movements.
- The item list gets stock for every item on the page from one aggregated query, `withSum('movements as stock', 'quantity')`. The list shows 20 items per page, ordered by name.
- The item page calls `currentStock()` directly.
- Low stock is `stock <= low_stock_threshold` (`InventoryItem::isLowStock`). It shows a red badge on the list and on the item page. Nothing else reacts to it. Because the threshold defaults to 0, an item at zero stock is flagged low when no threshold is set.
- Stock never goes negative. The check runs under the row lock, and `Out` or a negative `Adjustment` that would cross zero is refused. An exact landing on zero is allowed (covered by a test).

## When InsufficientStockException is raised

`InsufficientStockException` (extends `DomainException`) is raised in `RecordStockMovementAction` when the available stock plus the delta is below zero. That happens when:

- An `out` movement asks for more than is on hand.
- A negative `adjustment` would take the stock below zero.

Its message is built in code and is English only:

```text
Only {available} in stock, cannot remove {requested}.
```

`requested` is the absolute value of the delta. Example: 3 in stock and an `out` of 5 gives "Only 3 in stock, cannot remove 5."

The controller shows it on the `quantity` field through `inventory.flash.insufficient_stock`:

| Locale | Text |
|---|---|
| English | "Not enough stock: :detail" |
| Bangla | A translated prefix, followed by `:detail` |

In both locales the `:detail` part is the English exception message, so the stock detail is still in English. Nothing is written when the request is refused.

## Ledger rules

- `StockMovement` is append-only at the model level. Its `updating` and `deleting` events throw a `LogicException` ("Stock movements are append-only and cannot be edited." and "... cannot be deleted."). The documented fix for a mistake is an adjustment.
- The guard exists only in Eloquent model events. A query builder update, a raw SQL statement or a bulk delete does not fire them. No database trigger was found in the inventory migration (`database/migrations/2026_11_24_053000_create_inventory_tables.php`).

## Activity log

- Creating an item writes an entry through the `activity()` helper with the event name `inventory_item_created`, caused by the signed-in user.
- Recording a movement writes no activity log entry. The ledger row (`created_at`, `user_id`, `reason`) is the only record of who recorded it.
- The app has no activity feed screen.

## Screens

- **Items list**: a form to add an item (name, unit, low stock level) and a paginated table with name, unit, stock, status and a link to the ledger.
- **Item page**: current stock with unit, a form to record a movement (type, quantity, reason, note), and the ledger newest first, 25 rows per page, with the user who recorded each movement.

The quantity field uses a numeric keyboard hint (`inputmode="numeric"`). Validation enforces the integer rule. The field always shows the hint "Adjustments may be negative, e.g. -2", including for `in` and `out`.

## Commands

```bash
php artisan test --compact --filter=InventoryTest
```

See [Testing](testing.md) for the full suite.

## Limitations

- No category model, table or field exists. Items cannot be grouped, filtered or reported by category.
- Items cannot be edited or deleted after creation. There is no route for it, so a wrong name, unit or threshold can only be fixed in the database.
- There is no search or filter on the items list.
- Low stock is shown only as a badge. There are no alerts, notifications or reports. `docs/idea.md` lists low-stock alerts as a planned feature.
- The `InsufficientStockException` text is English only and hard-coded. The stock-type labels are hard-coded bilingual strings. Neither uses translation keys, and only the wrapper text is translated.
- Stock movements are not logged to the activity log. Only item creation is.
- The append-only rule is enforced in the model only (see Ledger rules).
- There are no purchase prices, supplier records, sales or cost fields. Stock is a count.
- Stock is one count per item for the whole school. There is no per-year, per-class or per-location split.
- The item list reads stock without a lock. The lock applies only to recording a movement, so the figure on screen can be slightly out of date.

## Open questions

- Should inventory connect to Accounts (purchases as expenses) or to Fees (sales)? The code has no link to either.
- Should a movement be editable as a correction instead of an adjustment? The code deliberately forbids edits, and no document records whether that is final.
