# Students

This page covers how students, guardians and enrollments are stored, the admission form and its validation, what admission writes, the edit flow and how files are kept, the list filters, the profile page, and how photos and guardian NID scans are served. The parent view of the same data is in [Parent portal](portal.md). Code lives in `app/Domain/Student`, `app/Http/Controllers/StudentController.php`, `app/Http/Requests` (`StoreStudentRequest`, `UpdateStudentRequest`, `IndexStudentsRequest`) and `resources/views/students`.

## Data model

| Model | Table | Key columns | Relations |
|---|---|---|---|
| `Student` | `students` | `student_code`, `name`, `name_bn`, `dob`, `gender`, `birth_reg_no`, `photo`, `blood_group`, `religion`, `address`, `status` | `guardians()` hasMany, `primaryGuardian()` hasOne where `is_primary`, `enrollments()` hasMany, `currentEnrollment()` hasOne where `status = active`, latest |
| `Guardian` | `guardians` | `student_id`, `name`, `name_bn`, `relation`, `mobile`, `nid`, `nid_front_path`, `nid_back_path`, `email`, `is_primary` | `student()` belongsTo |
| `Enrollment` | `enrollments` | `student_id`, `academic_year_id`, `class_id`, `section_id`, `roll_no`, `status` | `student()`, `academicYear()`, `schoolClass()` (via `class_id`), `section()`, `attendanceRecords()` |
| `SchoolClass` | `classes` | `name`, `name_bn`, `numeric_name`, `order` | `sections()`, `subjects()` |
| `Section` | `sections` | `class_id`, `name`, `name_bn` | `schoolClass()` |
| `AcademicYear` | `academic_years` | `name`, `is_current` | `enrollments()` |

Every model in this table uses `BelongsToSchool`, which adds a `school_id` scope to queries and fills `school_id` on create. `Student` also uses `SoftDeletes`.

Constraints from the migrations:

- `students`: unique `(school_id, student_code)`; `status` defaults to `active`; `gender` defaults to `male`.
- `guardians`: index on `(school_id, mobile)`. No unique rule on mobile, and none that allows only one primary guardian per student.
- `enrollments`: unique `(student_id, academic_year_id)`, so a student has one enrollment per academic year.
- `classes`: unique `(school_id, name)`. `sections`: unique `(class_id, name)`.

## Student code

The admission form never sends a code (`student_code` is not in the request rules). `AdmitStudentAction` generates `STU-{year}-{NNNNN}`, for example `STU-2026-00042`:

- `{year}` is `date('Y')` on the server at admission. It is the calendar year, not the academic year.
- `{NNNNN}` is the number of non-deleted students in the school plus 1, zero-padded to 5 digits. The sequence runs across all years and never resets.
- There is no retry. Two admissions at the same time, or a code still held by a soft-deleted student, can produce the same code. The unique index then rejects the save and the user gets a server error.

## Admission form

`GET /students/create` (`students.create`) shows the form and `POST /students` (`students.store`) saves it. Both need `create-students`. The form has six cards. A field is required when its rule in the table below starts with `required`; the page also shows a "Required field" hint.

| Card | Field (form label) | Input | Required | Rules (`StoreStudentRequest`) |
|---|---|---|---|---|
| 1. Basic information | `name` (Full name, English) | text | yes | string, max 255 |
| | `name_bn` (Full name, Bangla) | text | no | string, max 255 |
| | `dob` (Date of birth) | date | no | `Y-m-d`, before today, and after the date 25 years before today |
| | `gender` | select, default male | yes | `male`, `female` or `other` |
| | `blood_group` | select | no | `A+`, `A-`, `B+`, `B-`, `O+`, `O-`, `AB+`, `AB-` |
| | `religion` | select | no | `Religion` enum |
| 2. Academic information | `class_id` (Class) | select | yes | integer, must exist in this school's classes |
| | `section_id` (Section, optional) | select, all sections labelled "Class - Section" | no | integer, must exist in this school and belong to the submitted class |
| | `roll_no` (Roll no) | number | no | integer, 1 to 9999 |
| 3. Guardian information | `guardian_name` | text | yes | string, max 255 |
| | `guardian_relation` | select, default father | yes | `father`, `mother` or `other` |
| | `guardian_nid` | text (input limited to 17 characters) | no | 10, 13 or 17 digits |
| 4. Contact information | `guardian_mobile` | text | yes | `^01[3-9]\d{8}$` (11 digits) |
| 5. Address information | `address` | textarea | no | string, max 500 |
| 6. Documents and photograph | `birth_reg_no` (BDRIS birth registration number) | text (input limited to 17 characters) | no | exactly 17 digits, unique among this school's non-deleted students |
| | `photo` | file | no | image, `jpg`, `jpeg`, `png` or `webp`, max 2 MB (2048 KB) |
| | `guardian_nid_front` | file | no | `jpg`, `jpeg`, `png` or `pdf`, max 2 MB |
| | `guardian_nid_back` | file | no | `jpg`, `jpeg`, `png` or `pdf`, max 2 MB |

Before validation, `NormalizesInput` runs:

- Text fields (`name`, `name_bn`, `guardian_name`, `blood_group`, `address`) lose control characters, are normalised to NFC and trimmed.
- Number and date fields (`dob`, `roll_no`, `class_id`, `section_id`, `birth_reg_no`, `guardian_nid`) have Bangla digits converted to ASCII.
- `guardian_mobile` is converted to the `01XXXXXXXXX` form. A leading `+88` and spaces, dashes, dots and brackets are removed.

The mobile error message is hard-coded in Bangla inside `StoreStudentRequest`. `UpdateStudentRequest` uses the translation key `students.errors.invalid_mobile` instead.

## What admission writes

`AdmitStudentAction::execute()` runs one database transaction inside `CurrentSchool::runAs()` for the school:

1. **Student**: the generated `student_code`, name fields, `dob`, `gender` (male if missing), `birth_reg_no`, the photo path, `blood_group`, `religion`, `address`, and `status = active`.
2. **Primary guardian**: created when a guardian name or mobile is present. Both are required, so it is always created. Stores name, relation (default father), mobile, NID number, the NID scan paths, and `is_primary = true`. The form never sets `name_bn` or `email`, so both stay null.
3. **Enrollment**: created only when both `academic_year_id` and `class_id` are present. The academic year is the school's current year (`is_current`). Stores `section_id`, `roll_no` and `status = active`. If the school has no current academic year, the student is saved with no enrollment and no warning. Such a student has no class, does not match class filters, and shows no class on the portal.

The controller stores uploaded files before the action runs, under `storage/app/private/students/{school_id}/photo/` and `storage/app/private/students/{school_id}/guardian-nid/`, with generated file names. If the transaction fails, those files stay on disk.

On success the user is redirected to the list with "Student admitted successfully." No code in the student flow writes to the activity log.

## Edit student

`GET /students/{student}/edit` (`students.edit`) and `PUT /students/{student}` (`students.update`) need `edit-students`.

The form covers the personal fields, the photo, the primary guardian's name, relation, mobile and NID, and the two NID scans. The same rules apply as on admission, except that the BRN uniqueness check ignores the student being edited. The form does not show or change `student_code`, class, section, roll number, status or guardian email.

On save, in one transaction:

- The student is updated with `name`, `name_bn`, `dob`, `gender`, `blood_group`, `birth_reg_no`, `religion` and `address`, plus a new `photo` path when a file was uploaded.
- The primary guardian is updated with `updateOrCreate` on `is_primary = true`. Name, relation and mobile are always written. The NID number is cleared when the field is blank. NID scan paths change only when a new file is uploaded.

Update does not touch enrollments.

File handling:

- A new photo replaces the old one. An empty file input keeps the current photo. The old file is deleted from disk only after the transaction commits. There is no remove control.
- Each NID scan side is replaced on its own, with the same keep-current rule and delete-after-commit rule.
- New uploads are stored before the transaction starts. If the save fails, the new files remain on disk and the old files are kept.

On success the user is redirected to the profile with "Student details updated."

## List page

`GET /students` (`students.index`) needs `view-students`. Filters are validated by `IndexStudentsRequest` and applied in `StudentController::index()`.

| Parameter | Rule | Behaviour |
|---|---|---|
| `search` | nullable string, max 100 | Contains match on `name`, `name_bn`, `student_code` and `birth_reg_no`. `%` and `_` are matched literally. |
| `class_id` | nullable integer that exists in this school's classes | Students whose active enrollment is in that class. |
| `status` | `active` or `inactive` | `active` means status is active. `inactive` means any other status (inactive, transferred, graduated or dropped). |
| `sort` | `name` (default), `code`, `newest` | `name` sorts by the English name, `code` by student code, `newest` by id, descending. |

The list shows 15 students per page and keeps the query string across pages. A Reset link appears when any filter is set. Columns: roll number (active enrollment), student code, name (Bangla name, with the English name below when they differ), class and section, primary guardian mobile, and status.

## Profile page

`GET /students/{student}` (`students.show`) needs `view-students`. The Edit button appears only with `edit-students`.

- Header: the Bangla name in the Bangla locale, otherwise the English name. The English name and student code appear below it.
- Student card: photo (or a placeholder), Bangla name, English name, date of birth (d/m/Y), gender, blood group, religion, BRN, class and roll number from the active enrollment, and present address.
- Guardian card: name, relation, mobile, NID number, and links to view the front and back NID scans when they are stored.

The profile does not show section, status, admission date, academic year, attendance, fees or results.

## Photos and NID scans

| Route | Name | Returns | Permission | Not found |
|---|---|---|---|---|
| `GET /students/{student}/photo` | `students.photo` | Photo, served inline | `view-students` | 404 when there is no photo |
| `GET /students/{student}/guardian-nid/{side}` | `students.guardian-nid` | Front or back scan | `view-students` | 404 when there is no path. Any side other than `front` or `back` does not match the route. |

Files are stored on the `local` disk (`storage/app/private`), which is not web-accessible. The controller streams them with `Storage::disk('local')->response()`. The student is resolved through route model binding, so a student from another school is not found (inferred from the school scope).

Who can open them: every role with `view-students` (owner, teacher, accountant and super admin). Teachers and accountants can therefore open guardian NID scans. Parents cannot, and the portal shows neither the photo nor the scans.

## Leaving the roll

The application has no way for a student to leave the roll:

- There is no delete route or action. `Student` uses `SoftDeletes`, so a row could only be soft-deleted outside the app.
- Nothing in the app changes `status` after admission. `StudentStatus::Inactive`, `Transferred`, `Graduated` and `Dropped` have labels but no code path writes them.
- `delete-students`, `export-students` and `import-students` exist in the permission seeder, but no route or controller uses them.

The dashboard's active student counts include only students with `status = active` (`DashboardController`).

## Enum and fixed values

| Field | Values | Source |
|---|---|---|
| `StudentStatus` (`students.status`) | `active`, `inactive`, `transferred`, `graduated`, `dropped` | `app/Support/Enums/StudentStatus.php`, labels in `lang/en/enums.php` |
| `Religion` (`students.religion`) | `islam`, `hinduism`, `buddhism`, `christianity`, `other` | `app/Support/Enums/Religion.php`, labels in `lang/en/students.php` |
| Gender (`students.gender`, string) | `male`, `female`, `other` | Form and validation only |
| Guardian relation (`guardians.relation`, string) | `father`, `mother`, `other` | Form and validation only |
| Blood group (`students.blood_group`, string) | `A+`, `A-`, `B+`, `B-`, `O+`, `O-`, `AB+`, `AB-` | Form and validation only |

## Permissions by role

| Permission | Owner | Teacher | Accountant | Parent | Super admin |
|---|---|---|---|---|---|
| `view-students` | yes | yes | yes | no | yes |
| `create-students` | yes | no | no | no | yes |
| `edit-students` | yes | no | no | no | yes |
| `delete-students` | yes | no | no | no | yes (no route uses it) |
| `export-students`, `import-students` | yes | no | no | no | yes (no route uses them) |

Super admin receives every permission except `view-portal`. Source: `database/seeders/RoleAndPermissionSeeder.php`.

## Routes

| Method | Path | Name | Permission |
|---|---|---|---|
| GET | `/students` | `students.index` | `view-students` |
| GET | `/students/create` | `students.create` | `create-students` |
| POST | `/students` | `students.store` | `create-students` |
| GET | `/students/{student}` | `students.show` | `view-students` |
| GET | `/students/{student}/edit` | `students.edit` | `edit-students` |
| PUT | `/students/{student}` | `students.update` | `edit-students` |
| GET | `/students/{student}/photo` | `students.photo` | `view-students` |
| GET | `/students/{student}/guardian-nid/{side}` | `students.guardian-nid` | `view-students` |

Every route sits behind `auth` and the `module:students` middleware. With `KCAM_MODULE_STUDENTS=false` the routes return 404 and the menu item is hidden.

## Tests

Feature tests for this area: `tests/Feature/StudentAdmissionTest.php`, `StudentBangladeshAdmissionTest.php`, `StudentEditBangladeshTest.php`, `StudentListFiltersTest.php`, `StudentProfileTest.php` and `InputValidationTest.php`.

```bash
php artisan test --compact tests/Feature/StudentAdmissionTest.php
```

## Related

[Parent portal](portal.md), [Roles and permissions](roles-and-permissions.md), [Security and privacy](security-and-privacy.md), [Localization](localization.md), [Attendance](attendance.md), [Fees and payments](fees-and-payments.md), [Exams](exams.md), [Database](database.md), [Testing](testing.md).

## Limitations

- **Leaving the roll**: no UI or action changes status, deletes a student, moves a student to another class, or starts a new academic year. Enrollment is written only at admission. Promotion, transfer and withdrawal workflows are not built.
- **Edit scope**: class, section, roll number, student code, status and guardian email cannot be edited. Guardian `name_bn` and `email` are never captured by any form.
- **Photos and scans**: there is no remove control. Uploads are not cleaned up after a failed save.
- **Student code**: count-based. It can collide after a soft delete or under concurrent admissions, and the save then fails with a server error and no retry. The year is the admission calendar year.
- **No current academic year**: admission succeeds without an enrollment and shows no warning.
- **Guardians**: the code assumes one primary guardian. Nothing enforces a single primary guardian per student, and no UI adds a second guardian.
- **Status badge**: the list always shows the badge in the success colour, including for inactive students.
- **Phone list**: cells have no `data-label`, so on phones the rows show values without column titles.
- **Sorting**: `name` sorts by the English name, even in the Bangla locale.
- **Messages**: the mobile error is hard-coded Bangla in `StoreStudentRequest` but a translation key in `UpdateStudentRequest`.
- **Audit**: admission and edits write no activity log entries, even though `spatie/laravel-activitylog` is installed.
- **Blood group**: the edit form shows a literal `-` for the empty option instead of a translated label.
- **Missing files**: the behaviour when a stored photo or scan is missing on disk was not verified in code.

Open questions:

- How should a student leave the roll (status change, soft delete, or both), and which role may do it?
- Should teachers and accountants see guardian NID scans, or only owners?
