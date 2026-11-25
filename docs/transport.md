# Transport

This page covers school transport: vans (vehicles), routes that run on a van, and the assignment of enrolled students to a route with a monthly fee. Seats belong to the van, so several routes can share one van's capacity. Transport fees are recorded per student but are not billed. The fee module never reads them. The state described here was checked against the working tree on 2026-11-26.

## Code map

| Concern | File |
|---|---|
| Vehicle model | `app/Domain/Transport/Models/TransportVehicle.php` |
| Route model | `app/Domain/Transport/Models/TransportRoute.php` |
| Assignment model | `app/Domain/Transport/Models/TransportAssignment.php` |
| Seat assignment | `app/Domain/Transport/Actions/AssignStudentToRoute.php` |
| Domain exception | `app/Domain/Transport/TransportException.php` |
| Screens | `app/Http/Controllers/TransportController.php`, `app/Http/Controllers/TransportVehicleController.php` |
| Validation | `app/Http/Requests/SaveRouteRequest.php`, `app/Http/Requests/SaveVehicleRequest.php`, `app/Http/Requests/AssignStudentRequest.php` |
| Messages | `lang/en/transport.php`, `lang/bn/transport.php` |
| Views | `resources/views/transport/` (index, show, print, route-form, vehicle-form) |
| Tests | `tests/Feature/TransportTest.php` |

## Access and module switch

| Action | Route name | Permission | Granted to |
|---|---|---|---|
| Transport home, route roster, print sheet | `transport.index`, `transport.routes.show`, `transport.routes.print` | `view-transport` | owner, teacher, super_admin |
| Add or edit route, assign or remove student, add or edit vehicle | `transport.routes.create`, `transport.routes.store`, `transport.routes.edit`, `transport.routes.update`, `transport.routes.assign`, `transport.assignments.destroy`, `transport.vehicles.*` | `manage-transport` | owner, super_admin |

- All transport routes sit behind `module:transport`. The switch is `modules.transport` in `config/kcam.php`, set by `KCAM_MODULE_TRANSPORT`. When off, the routes return 404.
- Teachers see route rosters and the print sheet without any fee values. The fee column and total appear only for `manage-transport`.
- Accountants and parents have no transport access. Permissions come from `database/seeders/RoleAndPermissionSeeder.php`.

See [Roles and permissions](roles-and-permissions.md) for the full matrix.

## Data model

| Table | Columns | Notes |
|---|---|---|
| `transport_vehicles` | `school_id`, `van_number` string(50), `driver_name` string(150), `driver_mobile` string(20), `capacity` unsigned small integer, timestamps | Unique on `(school_id, van_number)`. No soft delete. |
| `transport_routes` | `school_id`, `vehicle_id` FK nullable (on delete: set null), `name` string(150), `name_bn` string(150) nullable, `stops` text nullable, timestamps | Index on `school_id` only. Route names are not unique. |
| `transport_assignments` | `school_id`, `route_id` FK (cascade), `enrollment_id` FK to `enrollments` (cascade), `stop` string(150) nullable, `monthly_fee` unsigned integer in poisha, timestamps | Unique on `enrollment_id`, so one enrollment rides one route. No soft delete. |

Model helpers:

- `TransportVehicle::riderCount(?int $exceptRouteId = null): int` counts assignments on every route that uses the van, optionally leaving one route out.
- `TransportRoute::stopList(): array` splits `stops` on line breaks, trims each line and drops empty lines.

## Vans and routes

Vehicle fields (`SaveVehicleRequest`):

| Field | Rule |
|---|---|
| `van_number` | required, max 50, unique within the school (the record itself is ignored on edit) |
| `driver_name` | required, max 150 |
| `driver_mobile` | required, `01[3-9]` followed by 8 digits, normalised first |
| `capacity` | required integer, 1 to 200 |

Route fields (`SaveRouteRequest`):

| Field | Rule |
|---|---|
| `name` | required, max 150 |
| `name_bn` | nullable, max 150 |
| `stops` | nullable, max 2000, one stop per line |
| `vehicle_id` | nullable, must be a vehicle of the same school |

Routes and vans have no delete action. Route stops are free text, and each assignment has its own `stop` text.

## Capacity rules

1. Seats belong to the van. `riderCount()` counts assignments across all routes on that van.
2. Adding a student (`AssignStudentToRoute::handle`) runs in one transaction. It locks the route row, then the vehicle row, counts riders, and rejects the request when the count is greater than or equal to `capacity`. The check and the insert are atomic.
3. Saving a route edit with a van chosen (`SaveRouteRequest`, update only) checks that the riders already on that van, excluding this route, plus this route's own riders, do not exceed its capacity. The error goes on `vehicle_id`.
4. Lowering a van's capacity (`SaveVehicleRequest`, update only) is refused when capacity is below the riders on all routes of that van. The error goes on `capacity`.
5. A route with no vehicle cannot take riders.
6. Clearing a route's vehicle skips the seat check (the check runs only when a vehicle is chosen) and leaves the existing assignments in place. Those riders then sit on a route with no van.

Checks 3 and 4 read the count without a row lock, so two simultaneous edits could both pass. Only check 2 is locked.

## Assigning students

Route: POST `/transport/routes/{transportRoute}/students` (`transport.routes.assign`). Fields from `AssignStudentRequest`:

| Field | Rule | Notes |
|---|---|---|
| `enrollment_id` | required integer | Must be an `active` enrollment in the school's current academic year. Otherwise error `choose_active_student`. |
| `stop` | nullable, max 150 | Free text. Not checked against the route's stop list. |
| `monthly_fee` | required, `^\d{1,9}(\.\d{1,2})?$` | Converted to poisha with `Money::toPoisha`. Zero is allowed for a free ride. Must not exceed 40,000,000 taka (`MAX_FEE_POISHA` = 4,000,000,000 poisha). |

The student picker lists active enrollments of the current year that are on no route, ordered by class and roll number, capped at 500 rows (`TransportController::candidates`).

`TransportController::assign` catches `TransportException` and returns the message on the `enrollment_id` field. Success shows the flash "Student assigned."

Removing a student: DELETE `/transport/assignments/{assignment}` (`transport.assignments.destroy`). This is a hard delete. The row and its fee are gone, and the seat is free again.

### TransportException messages

These three are the only exceptions in the domain. All are thrown from `AssignStudentToRoute::handle`.

| Key | English text (`lang/en/transport.php`) | Trigger |
|---|---|---|
| `transport.errors.no_vehicle` | This route has no vehicle yet. | The route's `vehicle` relation is null. |
| `transport.errors.vehicle_full` | The vehicle is full. | Riders on all routes of the van are greater than or equal to `capacity`. |
| `transport.errors.already_assigned` | This student already rides a route. | The insert hits the unique key on `transport_assignments.enrollment_id` (`UniqueConstraintViolationException` is translated). |

Validation messages (not exceptions):

| Key | English text | Field | Trigger |
|---|---|---|---|
| `transport.errors.choose_active_student` | Choose an active student of the current year. | `enrollment_id` | Enrollment is missing, not active, or from another year. |
| `transport.errors.fee_too_high` | The monthly fee cannot exceed 40,000,000 taka. | `monthly_fee` | Fee above `MAX_FEE_POISHA`. |
| `transport.errors.vehicle_over_capacity` | This vehicle would carry :riders riders, more than its :seats seats. | `vehicle_id` | Route save would overfill the new van. |
| `transport.errors.capacity_below_riders` | Capacity cannot be below the :riders riders already assigned. | `capacity` | Vehicle capacity lowered below its riders. |

The Bangla equivalents are in `lang/bn/transport.php`.

## Fees and invoices

- `monthly_fee` is stored per assignment in poisha. The route page shows the sum as "Monthly total". The roster and print sheet show each fee and a total, but only to `manage-transport` users.
- Transport charges do not reach invoices. `GenerateMonthlyInvoicesAction` builds invoices only from `FeeStructure` rows whose fee head is monthly. Nothing outside `app/Domain/Transport` reads `monthly_fee`, and no fee head is seeded for transport. The fee module, payments, receipts, dues and the accounts cash book do not include transport amounts.
- A school that bills transport has to do it outside the app, or set up a monthly fee head and structure in [Fees and payments](fees-and-payments.md) that is not linked to the route assignment.

## Commands

```bash
php artisan test --compact --filter=TransportTest
```

See [Testing](testing.md) for the full suite.

## Limitations

- Routes and vans cannot be deleted, and no action retires a van that is out of service.
- Unassigning a student is a hard delete. No history of past riders or fees is kept.
- Route names are not unique, and nothing prevents two routes with the same name.
- A student's stop is free text. It is not checked against the route's stop list, so stop names can drift.
- The student picker shows at most 500 candidates. A school with more unassigned students cannot pick the rest from the screen.
- The capacity checks for route and vehicle edits are not row-locked (see capacity rule 3 and 4).
- Clearing a route's vehicle leaves riders on a route that has no van. Nothing reassigns or flags them.
- Transport fees are not invoiced, not collected and not reported. There is no fee report for transport.
- An assignment's fee can be set only when the student is added. No edit action exists for an assignment, so a fee change means removing the student and assigning them again. The fee has no effective dates.
- The transport tables have no timetable, pickup time or drop time fields. `docs/idea.md` lists pickup and drop SMS as a future idea, and no such feature is built.

## Open questions

- Should transport fees become invoice items, and if so, per route or per student? The code does not say.
- Should a route be blocked from keeping riders after its vehicle is cleared? The code allows it, and nothing in the repository states the intended behaviour.
