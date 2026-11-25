# Exams, marks and results

This page covers exams, subject marks, the marks entry screen, grading, and where results appear. The main code is `ExamController`, `UpdateMarksRequest`, `GradeMarks`, and the `Exam`, `Mark` and `Subject` models. Results shown to parents are described in [Parent portal](portal.md). Role setup is in [Roles and permissions](roles-and-permissions.md).

## Current scope

Staff can list exams and enter marks. The application has no screen or route to create, edit, delete or publish an exam. The exams list empty state says "The ability to add exams is coming soon." Exams come from seeders, factories or direct database writes.

## Data model

Table `exams` (migration `2026_11_19_150019_create_exams_table.php`):

| Column | Type | Notes |
|---|---|---|
| `school_id` | FK to `schools` | Cascade on delete |
| `academic_year_id` | FK to `academic_years` | Cascade on delete |
| `name` | string(100) | English name |
| `name_bn` | string(100), nullable | Bangla name |
| `starts_on`, `ends_on` | date, nullable | |
| `published_at` | timestamp, nullable | The only publish flag. Null means unpublished |

Index: (`school_id`, `academic_year_id`).

Table `subjects` (`App\Domain\School\Models\Subject`). Fillable fields besides `school_id`:

| Field | Notes |
|---|---|
| `class_id` | The `SchoolClass` the subject belongs to |
| `name`, `name_bn` | Bilingual name |
| `code` | Subject code |
| `full_marks` | Integer. Used for validation and grading |
| `pass_marks` | Integer. Stored, but no grading code reads it |
| `is_optional` | Boolean |

Table `marks` (migration `2026_11_19_150020_create_marks_table.php`):

| Column | Type | Notes |
|---|---|---|
| `school_id` | FK to `schools` | Cascade on delete |
| `exam_id` | FK to `exams` | Cascade on delete |
| `enrollment_id` | FK to `enrollments` | Cascade on delete |
| `subject_id` | FK to `subjects` | Cascade on delete |
| `marks` | decimal(5,2), nullable | The score |
| `grade` | string(10), nullable | Letter grade, set on save |
| `gp` | decimal(3,2), nullable | Grade point, set on save |

Unique: (`exam_id`, `enrollment_id`, `subject_id`). Index: (`school_id`, `exam_id`, `enrollment_id`).

Relations: `Exam` belongs to `AcademicYear` and has many `Mark`. `Mark` belongs to `Exam`, `Enrollment` and `Subject`.

### Bilingual names

`Exam`, `Subject`, `SchoolClass`, `Section` and `Student` each have an English and a Bangla name. Every screen shows the Bangla name when it is set and the English name otherwise. The views do not check the app locale, so the display does not switch with the interface language. The exams list shows `name_bn ?? name`. The marks screen header shows the Bangla name, then `/`, then the English name.

## Access

Routes are in `routes/web.php` inside the `module:exams` group.

| Method | URI | Route name | Gate | Action |
|---|---|---|---|---|
| GET | `/exams` | `exams.index` | `can:view-exams` | `ExamController@index` |
| GET | `/exams/{exam}/marks` | `exams.marks.edit` | `can:enter-marks` | `ExamController@editMarks` |
| PUT | `/exams/{exam}/marks` | `exams.marks.update` | `can:enter-marks` | `ExamController@updateMarks` |

Permissions from `database/seeders/RoleAndPermissionSeeder.php`:

| Role | view-exams | enter-marks | manage-exams | publish-results |
|---|---|---|---|---|
| Owner | yes | yes | yes | yes |
| Teacher | yes | yes | no | no |
| Super admin | yes | yes | yes | yes |
| Accountant | no | no | no | no |
| Parent | no | no | no | no |

`manage-exams` and `publish-results` are seeded, but no route or request checks them. Teachers are not linked to particular subjects or classes. Any user with `enter-marks` can enter marks for any subject in the school.

## Marks entry

1. The exams list shows exams for the current academic year only, with dates and a Published or Unpublished badge. The "Enter marks" link appears only for unpublished exams and only for users with `enter-marks`.
2. The marks screen shows a subject dropdown listing every subject in the school, labelled "Class - Subject". Choose one and press Show.
3. The grid lists enrollments with `status = active` in the subject's class and in the exam's academic year, ordered by roll number. Each student has one input.
4. Save sends `PUT` with `subject_id` and `marks[enrollmentId]`.
5. On success the page returns to the same subject with `exams.flash.marks_saved`.

Validation in `UpdateMarksRequest`:

| Field | Rules |
|---|---|
| Authorisation | The user has `enter-marks`, and the exam's `published_at` is null. A published exam returns 403 |
| `subject_id` | Required, integer, must exist in the subjects of the user's school |
| `marks` | Required array |
| `marks.*` | Nullable, numeric, minimum 0, maximum is the subject's `full_marks` (100 if the subject is not found) |
| Keys of `marks` | Each must be an enrollment in the subject's class and the exam's academic year. Otherwise the error `Invalid student for this subject.` |

Before validation, `prepareForValidation()` converts Bangla digits in each value to ASCII and trims it.

Save behaviour in `ExamController::updateMarks()`:

- The whole save runs in one `DB::transaction`.
- An empty or null value deletes that student's mark row for the subject. The result is no mark at all, not a zero.
- A value of 0 is stored as 0 and graded F.
- Any other value gets its grade and grade point from `GradeMarks::for(value, full_marks)`. The row is then written with `updateOrCreate`, keyed by exam, subject and enrollment. `school_id` comes from the exam.

The marks screen shows only the input for each student. Grades and grade points are not displayed to staff on any screen.

## Grading

`GradeMarks` (`App\Domain\Exams\Actions\GradeMarks`) computes `percent = marks / full_marks * 100` and applies this scale. The boundaries are inclusive at the lower end.

| Percent of full marks | Grade | Grade point |
|---|---|---|
| 80 or more | A+ | 5.0 |
| 70 to under 80 | A | 4.0 |
| 60 to under 70 | A- | 3.5 |
| 50 to under 60 | B | 3.0 |
| 40 to under 50 | C | 2.0 |
| 33 to under 40 | D | 1.0 |
| Under 33 | F | 0.0 |

Rules:

- The class docblock calls this the "Bangladesh primary GPA scale". The scale is hard-coded in the class. No per-school or per-exam setting exists.
- If `full_marks` is 0 or less, the percent is 0 and the grade is F.
- `pass_marks` on the subject is not used. The F boundary is fixed at 33 percent.
- Grade and grade point are computed when marks are saved and stored on the row. Changing a subject's full marks later does not recalculate saved grades. No recalculation command was found.
- There is no aggregate. The code computes no total, average, overall GPA or class position for a student. Each mark carries its own grade.

Tests in `tests/Feature/ExamMarksTest.php` check the grade scale against the GPA table.

## Results

There is no report card or result sheet. Results appear in three places:

| Surface | Shows | Filter | Who sees it |
|---|---|---|---|
| Exams list (`exams.index`) | Exam name, dates and a Published or Unpublished badge | Current academic year | Owner, Teacher, Super admin |
| Parent portal child page | Marks, grade and grade point, grouped by exam (`marks \| grade \| gp`) | Only exams with `published_at` set, in `PortalController::publishedResults()` | Parent linked to the child |
| Owner and teacher dashboards | Up to 3 exams of the current academic year that start today or later, by name and start date | No published filter | Owner, Teacher |

## Publishing

- `published_at` is set only by code or directly in the database. No route, action or screen sets or clears it.
- When it is set, the exams list shows Published and hides "Enter marks". Saving marks returns 403 (`UpdateMarksRequest::authorize()`). The parent portal shows that exam's marks.
- The marks GET screen does not check the publish state. The form opens for a published exam, and the save then fails with 403 and no message.
- No unpublish action exists.

## Tests

```bash
php artisan test --compact tests/Feature/ExamMarksTest.php
```

`ExamMarksTest.php` covers: the grade scale, the marks grid for a teacher, saving with grades, marks above full marks being rejected, an enrollment outside the subject's class being rejected, published exams being locked, an accountant being denied, another school's exam not being found, a parent being denied the exam list, and a subject from another school being rejected.

## Limitations

- No screen or route creates, edits, deletes or publishes an exam. `manage-exams` and `publish-results` are seeded but unused.
- The grade scale is hard-coded. `pass_marks` is ignored. The app has no GPA total, average or class position.
- Teachers are not linked to subjects or classes. Any user with `enter-marks` can edit marks for any subject in the school.
- `UpdateMarksRequest::after()` checks the enrollment's class and academic year, but not its status. An inactive enrollment in that class can be posted. The grid itself lists active students only.
- The marks GET screen does not check the publish state. Users see the form for a published exam, then get a bare 403 on save.
- A blank cell silently deletes an existing mark. Mark changes are not recorded in the activity log.
- Validation messages `Invalid student for this subject.` and the rest of the messages in this request are hard-coded English.
- The exams list shows only the current academic year. Past exams are not listed anywhere in the UI.
- The upcoming-exams list on the dashboards includes unpublished exams.
- Saved grades are not recalculated when a subject's full marks change.
