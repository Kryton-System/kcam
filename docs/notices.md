# Notices

Notices are school messages that staff publish to everyone, parents, teachers or one class. This page covers the data model, who can publish and read notices, where they appear, and the bilingual display rules. The main code is `NoticeController`, `StoreNoticeRequest`, the `Notice` model and `PortalController::noticesFor()`. The surfaces that show notices are described in [Dashboards](dashboards.md) and [Parent portal](portal.md), and the roles are in [Roles and permissions](roles-and-permissions.md).

Delivery is record-only today. The channel is saved, but no code sends SMS or push messages. See Limitations.

## Data model

Table `notices` (migration `2026_11_19_150021_create_notices_table.php`):

| Column | Type | Notes |
|---|---|---|
| `school_id` | FK to `schools` | Cascade on delete |
| `title` | string(255) | English title |
| `title_bn` | string(255), nullable | Bangla title |
| `body` | text | English body |
| `body_bn` | text, nullable | Bangla body |
| `audience` | string(30), default `all` | `all`, `class`, `section`, `parents` or `teachers` |
| `target_class_id` | FK to `classes`, nullable | Set null on delete. Used for the class audience |
| `channel` | string(20), default `app` | `app`, `sms` or `both` |
| `sent_at` | timestamp, nullable | Set to the save time |
| `created_by` | FK to `users`, nullable | Set null on delete |
| `created_at`, `updated_at` | timestamp | |

Indexes: (`school_id`, `audience`) and (`school_id`, `sent_at`).

`App\Domain\Notices\Models\Notice` casts `sent_at` to a datetime. It has the relations `targetClass()` (`SchoolClass`) and `author()` (`User`).

There is no draft state. `NoticeController::store()` sets `sent_at` to now, so every saved notice is live at once. There is no `section_id` column. A section audience stores only `target_class_id`.

## Who publishes and reads

Routes are in `routes/web.php` inside the `module:notices` group.

| Method | URI | Route name | Gate | Action |
|---|---|---|---|---|
| GET | `/notices` | `notices.index` | `can:view-notices` | `NoticeController@index` |
| POST | `/notices` | `notices.store` | `can:send-notices` | `NoticeController@store` |

Permissions from `database/seeders/RoleAndPermissionSeeder.php`:

| Role | view-notices | send-notices |
|---|---|---|
| Owner | yes | yes |
| Teacher | yes | yes |
| Super admin | yes | yes |
| Accountant | yes | no |
| Parent | yes | no |

Teachers can publish to every audience, including everyone and parents. No approval step exists. The notice form is rendered only when `send-notices` is held (`$canSend` in `notices/index.blade.php`).

Publish flow in `NoticeController::store()`: the validated data is saved with `school_id` from the user, `created_by` set to the user and `sent_at` set to now. An activity log entry `notice_published` is written with the audience and channel as properties. Nothing reads that entry. The redirect carries `notices.flash.published`.

## Form fields and validation

The form (`notices/index.blade.php`) collects title, body, audience, target class and channel. `StoreNoticeRequest` accepts more fields than the form sends.

| Field | Rules | In the form |
|---|---|---|
| `title` | Required, string, max 255 | Yes |
| `title_bn` | Nullable, string, max 255 | No |
| `body` | Required, string, max 5000 | Yes |
| `body_bn` | Nullable, string, max 5000 | No |
| `audience` | Required, one of `all`, `class`, `section`, `parents`, `teachers` | Yes, without `section` |
| `target_class_id` | Nullable. Required when audience is `class` or `section`. Integer. Must exist in the school's classes | Yes, optional select |
| `channel` | Required, one of `app`, `sms`, `both` | Yes |

Text fields are cleaned by `NormalizesInput::cleanText()`. Control characters are removed, the text is normalised to Unicode NFC and it is trimmed.

SMS length: when `channel` is `sms` or `both`, `body` and `body_bn` must be 480 characters or fewer (`StoreNoticeRequest::MAX_SMS_CHARACTERS`). The message for this rule is hard-coded English.

`target_class_id` is saved whenever it is sent, even for audiences that do not use it. A notice sent to everyone with a class selected keeps that class, and the list shows the class name.

## Where notices appear

| Surface | Query | Filter | Who sees it |
|---|---|---|---|
| `/notices` | All notices of the school, latest first, 15 per page | None | Any user with `view-notices`. Parents see it if they type the URL |
| Owner dashboard | Latest 3 by `created_at` | None | Owner and Super admin (default dashboard branch) |
| Teacher dashboard | Latest 3 by `created_at` | None | Teacher |
| Parent portal child page | `PortalController::noticesFor()`: sent notices with audience `all` or `parents`, or audience `class` with `target_class_id` equal to the child's current class. Latest 10 by `sent_at` | Audience and class | Parent linked to the child |

The accountant dashboard has no notice list. The portal does not include the `teachers` or `section` audiences.

## Bilingual display

- **Input**: the form sends only the English title and body. `title_bn` and `body_bn` are accepted by the request and the controller, but no screen collects them. In the UI they stay empty unless they are set outside the app.
- **Display**: every surface shows the Bangla field when it is set and the English field otherwise (`title_bn ?? title`, `body_bn ?? body`). When a Bangla body exists, the English body is not shown anywhere.
- **Locale**: the app locale does not change which field is shown.
- **Meta line** on `/notices`: the author's name, then the sent time in `d/m/Y H:i` (PHP format, so digits are ASCII in both languages), then the target class name (`name_bn ?? name`).
- **Line breaks**: the body keeps its line breaks on `/notices` (`whitespace-pre-line`).

## Gates

- `module:notices` on the route group. A disabled module returns 404.
- `view-notices` on the list.
- `send-notices` on the store route and in `StoreNoticeRequest::authorize()`.
- The dashboards have no notice gate of their own. They follow the role branch in `DashboardController`. The portal follows `view-portal`.

## Tests

```bash
php artisan test --compact tests/Feature/ParentPortalTest.php
php artisan test --compact tests/Feature/InputValidationTest.php
```

`ParentPortalTest.php` has the case `notices respect audience and class targeting`. `InputValidationTest.php` has the cases `a valid notice is stored for the current school` and `sms notices are capped so one notice cannot burn the credit balance`. No test file is named for notices.

## Limitations

- **No delivery**: the `app`, `sms` and `both` channels are recorded only. No SMS or push code exists. `SmsLog` (`App\Domain\Notices\Models\SmsLog`) has a model and a factory, but no code writes to it. `send-sms` and `view-sms-logs` are seeded and unused. `sms_enabled` is written into school settings at setup and read nowhere. The 480 character rule checks input only.
- **Bangla text cannot be entered**: the form has no Bangla title or body field, so the Bangla display path is unused in practice.
- **No audience filter on `/notices`**: any user with `view-notices` sees every notice of the school, including teacher-only notices and notices for other classes. Parents can open the page by URL.
- **Section audience**: accepted by validation, missing from the form, has no `section_id` column, and is not shown in the parent portal.
- **No review step**: teachers can send to everyone and to parents without approval.
- **No edit, delete, withdraw or scheduling**. Read receipts are not tracked.
- **Class label on non-class notices**: `target_class_id` is saved for any audience, so a notice meant for everyone can show a class name.
- **Activity log**: `notice_published` is written, but no screen reads it. The app has no recent-activity feed.
- **English messages**: the SMS length message is hard-coded English.
- **Dates**: the sent time uses ASCII digits in the Bangla interface as well.
