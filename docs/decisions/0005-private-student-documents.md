# 0005. Private student documents

**Status:** Accepted

## Context

The student record holds a child's photo and, through the guardian, scans of the guardian's national ID (front and back). These are sensitive personal data. A file in a public folder can be fetched by anyone who has or guesses its URL, and the school has no CDN or signed-URL service. The uploads must stay private while staff with the right permission can still see them.

## Decision

- Uploads go to the private `local` disk, whose root is `storage/app/private`. The `public` disk is defined but no code uses it, and no `storage:link` symlink exists.
- Paths follow `students/<school_id>/photo/<file>` and `students/<school_id>/guardian-nid/<file>`. The school id in the path keeps files apart by school.
- The database keeps only the relative path, in `students.photo`, `guardians.nid_front_path` and `guardians.nid_back_path`.
- Files are served only by the app, through two routes that require `view-students`: `students.photo` and `students.guardian-nid`. The `{student}` parameter is resolved through the school scope, so a student from another school returns 404 (`app/Support/Traits/BelongsToSchool.php`).
- On update, the old file is deleted only after the new record is saved, so a failed save never loses a document (`StudentController::update`).
- Validation: photos are `image` JPG, PNG or WebP up to 2048 KB. NID scans are `file` JPG, PNG or PDF up to 2048 KB (`StoreStudentRequest`, `UpdateStudentRequest`).

Code evidence:

- `config/filesystems.php` (`local` root `storage/app/private`, `public` root `storage/app/public`)
- `app/Http/Controllers/StudentController.php` (`storeUpload`, `photo`, `guardianNid`, `update`)
- `routes/web.php` (`students.photo`, `students.guardian-nid`)
- `app/Http/Requests/StoreStudentRequest.php`, `app/Http/Requests/UpdateStudentRequest.php`
- `database/migrations/2026_11_24_073015_add_nid_documents_to_guardians_table.php`
- `database/migrations/2026_11_19_150007_create_students_table.php` (`photo` column)

## Consequences

- Every view streams the file through PHP. This costs more than a static file, but it keeps the permission check on each request.
- Anyone with `view-students` in the school can open any student's photo and NID scans. The check is per permission, not per child. The teacher role holds `view-students`, so teachers can open NID scans. Review this with [Security and privacy](../security-and-privacy.md) before the next release.
- Nothing removes a retired student's files. There is no student delete route. An update deletes only the file it replaces, and only after the new record is saved (`StudentController::update`). Files for retired students stay until someone removes them by hand.
- Files are not encrypted by the application. The NID number itself is also stored as plain text in `guardians.nid`. Protection relies on the server's file permissions and on backups being encrypted.
- Backups and restores must include `storage/app/private`, or the database rows point at missing files. Deployment steps are in [Deployment](../deployment.md#storage-and-private-uploads).
- A future move to object storage would keep the same routes and change only the disk.
