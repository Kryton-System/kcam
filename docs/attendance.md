# Attendance

This page covers how KCAM records daily attendance for each enrolled student, who can mark it, where the numbers appear, and what the code enforces today. The main code is `AttendanceController`, `RecordAttendanceAction` and the `AttendanceRecord` model. Parent and staff views are described in [Dashboards](dashboards.md) and [Parent portal](portal.md).

## Statuses

`App\Support\Enums\AttendanceStatus` is a string-backed enum with four cases. Only these values are accepted on save (`Rule::enum`).

| Case | Stored value | English label | Bangla label |
|---|---|---|---|
| `Present` | `present` | Present | উপস্থিত |
| `Absent` | `absent` | Absent | অনুপস্থিত |
| `Late` | `late` | Late | বিলম্বিত |
| `Excused` | `excused` | Excused | ছুটি |

Labels come from `enums.attendance_status.*` in `lang/en/enums.php` and `lang/bn/enums.php`. The stored column is `status` (string, 20 characters, default `present`).

## Data model

Table `attendance_records` (migration `2026_11_19_150011_create_attendance_records_table.php`):

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Primary key |
| `school_id` | FK to `schools` | Cascade on delete. Set by the `BelongsToSchool` trait |
| `enrollment_id` | FK to `enrollments` | Cascade on delete |
| `date` | date | Cast to `Y-m-d` |
| `status` | string(20) | Default `present` |
| `marked_by` | FK to `users`, nullable | Set to the saving user. Null when that user is deleted |
| `client_uuid` | uuid, nullable | Reserved for offline sync, see below |
| `synced_at` | timestamp, nullable | Set to the save time on every save |
| `created_at`, `updated_at` | timestamp | |

Indexes: unique (`enrollment_id`, `date`), plus (`school_id`, `date`) and (`school_id`, `status`).

Each row is one student's status on one date. It links to an `Enrollment`, not directly to the student. An enrollment ties a student to a class, section and academic year, so attendance follows the enrollment. `Enrollment::attendanceRecords()` returns the rows. `AttendanceRecord::enrollment()` returns the enrollment and `AttendanceRecord::marker()` returns the user who saved it.

## Routes and access

Routes are in `routes/web.php` inside the `module:attendance` group. A module switched off in `config/kcam.php` returns 404 (`EnsureModuleEnabled`).

| Method | URI | Route name | Gate | Action |
|---|---|---|---|---|
| GET | `/attendance` | `attendance.index` | `can:take-attendance` | `AttendanceController@index` |
| POST | `/attendance` | `attendance.store` | `can:take-attendance` | `AttendanceController@store` |

`IndexAttendanceRequest` and `StoreAttendanceRequest` check `take-attendance` again in `authorize()`.

Permissions from `database/seeders/RoleAndPermissionSeeder.php`:

| Role | take-attendance | view-attendance | edit-attendance | Attendance screens |
|---|---|---|---|---|
| Owner | yes | yes | yes | Register. Today's figures on the owner dashboard |
| Teacher | yes | yes | no | Register. Today's figures and per-class rows on the teacher dashboard |
| Super admin | yes | yes | yes | Register. No dashboard of its own, so it falls through to the owner view |
| Accountant | no | no | no | None. The accountant dashboard has no attendance figures |
| Parent | no | yes | no | Child's summary on the portal, gated by `view-portal` |

`view-attendance` and `edit-attendance` are seeded, but no route, request or view checks them. See Limitations.

## Marking the register

1. Open **Attendance**. The class defaults to the first class by `order`. The section filter is optional. The date defaults to today.
2. The grid lists enrollments with `status = active` in that class, for the school's current academic year, ordered by `roll_no`. Section filtering applies when a section is chosen.
3. Each student has four radio buttons. A student with no record for that date shows Present, so an untouched class is saved as Present.
4. Save posts `date` and `attendance[enrollmentId] = status` for every row in the grid. The controller calls `RecordAttendanceAction::execute()` once per row.
5. The page redirects back with the flash message `attendance.flash.saved`.

Because every row is posted, saving the register writes a record for every student shown, including students left at the default Present.

Validation in `StoreAttendanceRequest`:

| Field | Rules |
|---|---|
| `date` | Required. Format `Y-m-d`. From 2000-01-01 to today (`before_or_equal:today`) |
| `attendance` | Required array of 1 to 500 rows (`MAX_ROWS = 500`) |
| `attendance.*` | Required. Must be an `AttendanceStatus` value |
| Keys of `attendance` | Must be integers (enrollment IDs). Otherwise an error on `attendance` |

`IndexAttendanceRequest` checks `class_id` and `section_id` against the school's classes and sections. It applies the same date range to `date`. All three fields are optional. Bangla digits and surrounding spaces in `date`, `class_id` and `section_id` are normalised by `NormalizesInput` before validation.

## One record per enrollment per day

The rule is enforced in two places:

- **Database**: the unique index on (`enrollment_id`, `date`) in the migration.
- **Application**: `RecordAttendanceAction::record()` looks up the row by school, enrollment and date. It updates the row if one exists and creates it otherwise. Saving the same register twice does not create duplicates. `tests/Feature/AttendanceTest.php` covers this with the test `record attendance action creates or updates attendance idempotently`.

The lookup and the insert are not wrapped in a lock or a transaction. Two saves for the same student and date that run at the same moment can both miss the lookup. The unique index then rejects the second insert, and the request fails with a database error that the code does not catch. This race was not tested.

## Editing past attendance

- There is no separate edit screen. Open an earlier date in the register and save it. That overwrites the status of every student shown.
- Any date from 2000-01-01 to today can be changed. Future dates are rejected.
- Each save sets `marked_by` to the current user and `synced_at` to the current time. No history of earlier statuses is kept.
- Who can edit: any user with `take-attendance` (Owner, Teacher, Super admin). The seeded `edit-attendance` permission is not checked anywhere, so it has no effect.
- There is no lock period. A register can be changed weeks later by any user who can take attendance.
- The bulk save runs one update or insert per student, with no database transaction around the loop. If the request fails part way, the rows saved before the failure stay saved. Marks entry does use a transaction. Attendance does not.

## Offline sync fields

`client_uuid` and `synced_at` exist for offline sync. `RecordAttendanceAction::execute()` accepts a client UUID and keeps an existing one. The web register never passes one, and the application has no API routes (`routes/` holds only `web.php` and `console.php`). Treat both columns as reserved until a sync endpoint exists.

## Where summaries appear

| Where | Source | Content | Who sees it |
|---|---|---|---|
| Owner dashboard (`dashboard.blade.php`) | `DashboardController::attendanceToday()` | Today's present count out of the marked total, and the present rate | Owner and Super admin |
| Teacher dashboard (`dashboard-teacher.blade.php`) | `attendanceToday()` and `classRows()` | Present and absent counts for today, students not yet marked (active students minus marked), and per-class rows: students, present, absent and a done or pending status (done when every active student in the class has a record today) | Teacher |
| Parent portal child page (`portal/show.blade.php`) | `PortalController::attendanceSummary()` | Count for each status across all records of the child's current enrollment, plus a total | Parent linked to the child by mobile number |
| Register (`attendance/index.blade.php`) | Page data | The date and the total student count only. No status totals | Owner, Teacher |

The dashboard rate is present divided by marked, rounded to one decimal place. Marked means students with a record for today. Late and excused count as not present in the rate. Students with no record today are not in the denominator.

"Today" on the dashboards is `now()`, so it follows the app timezone.

The parent summary covers only the child's current enrollment. Attendance recorded under an earlier enrollment, such as a previous class or year, is not included.

## Tests

```bash
php artisan test --compact tests/Feature/AttendanceTest.php
php artisan test --compact tests/Feature/DashboardRolesTest.php
```

`AttendanceTest.php` has one test case, the idempotent action test. `DashboardRolesTest.php` checks the teacher attendance card and class rows, and the owner dashboard figures.

## Limitations

- No teacher to class or section assignment exists in the code. Any user with `take-attendance` can mark any class in the school.
- `StoreAttendanceRequest` checks only that keys are integers. `AttendanceController::store()` checks only that each enrollment exists in the school. A posted enrollment from another class, an inactive enrollment or an earlier academic year is accepted. The register shows only active enrollments of the current year, so this affects crafted requests only.
- The bulk save has no transaction. A failure part way leaves a partial register.
- Concurrent saves for the same student and date can hit the unique index and return an unhandled database error. This was not tested.
- Edit history is not kept. `marked_by` holds only the last user who saved the row.
- `view-attendance` and `edit-attendance` are seeded, but no route, request or view checks them.
- `client_uuid` and `synced_at` are written, but no sync path exists.
- The register shows no status totals. Totals appear only on the dashboards and the portal.
- The dashboard rate counts late and excused as not present.
- The owner dashboard shows only present out of marked, and the rate. Absent, late and excused counts are computed by `attendanceToday()` but not displayed.
- The message `Invalid enrollment id.` in `StoreAttendanceRequest` is hard-coded English.
- The parent summary ignores earlier enrollments. Attendance history across years is not shown anywhere.
