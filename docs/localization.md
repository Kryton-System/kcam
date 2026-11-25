# Localization

KCAM supports English (`en`) and Bangla (`bn`). The language a visitor sees comes from a session choice made with the language switcher, and falls back to `APP_LOCALE`. This page covers locale setup, the language files, number and date formatting, Bangla digit input, fonts, and how to add text in both languages.

## Default locale and fallback

| Setting | Read in | Default | Effect |
|---|---|---|---|
| `APP_LOCALE` | `config/app.php` (`locale`) | `en` | Language used until a visitor picks one |
| `APP_FALLBACK_LOCALE` | `config/app.php` (`fallback_locale`) | `en` | Language used when a key is missing in the active language |

`.env.example` sets both to `en`. A local `.env` that sets `APP_LOCALE=bn` makes Bangla the default for that checkout. This also changes test results (see [Testing](testing.md)).

## Choosing a language

The choice is stored in the session under the key `locale`. It is not saved on the user account, so a new device or a new session starts from `APP_LOCALE`.

`App\Http\Middleware\SetLocale` runs on every web request (appended to the `web` group in `bootstrap/app.php`):

1. Read `session('locale')`.
2. If the value is in `SetLocale::SUPPORTED` (`['en', 'bn']`), use it.
3. Otherwise use `config('app.locale')`.

The middleware then calls `app()->setLocale()` for the request.

| Method | URI | Name | Handler | Behaviour |
|---|---|---|---|---|
| GET | `/language/{locale}` | `locale.switch` | `App\Http\Controllers\LocaleController` (invokable) | The route only matches `en` or `bn` (other values return 404). The controller stores the locale in the session and redirects back to the previous page. |

The switch is available to guests and signed-in users. `<x-language-switcher />` renders the two links (`বাংলা` and `English`) with `hreflang`, `lang` and `aria-current="true"` on the active one. It appears in the desktop sidebar and the mobile top bar (`components/app-layout.blade.php`), in `components/guest-layout.blade.php` (login and register), and on the landing page (`welcome.blade.php`).

Layouts and the landing page set `<html lang="...">` from `app()->getLocale()`, so Bangla pages declare `lang="bn"`. That attribute also selects the Bangla font stack (see Fonts below).

## Supported locales

| Code | Label in switcher | Digits in formatted output | Month and day names | HTML lang |
|---|---|---|---|---|
| `en` | English | ASCII 0 to 9 | English | `en` |
| `bn` | বাংলা | Bangla ০ to ৯ | Bangla | `bn` |

Stored values are always ASCII digits. Only display changes (see Formatting below).

## Language files

Both `lang/en` and `lang/bn` contain PHP arrays with the same file names, except that `pagination.php` and `passwords.php` exist only in `en`. Key counts are flattened (nested keys counted individually).

| File | Covers | Keys en | Keys bn |
|---|---|---|---|
| `accounts.php` | Accounts index, expenses, cash book, flash messages | 51 | 51 |
| `app.php` | Application name, tagline, short name, logout, navigation labels | 26 | 26 |
| `attendance.php` | Attendance index and flash messages | 13 | 13 |
| `auth.php` | Login and register pages, failed and throttle messages | 40 | 40 |
| `common.php` | Offline page text and the confirm dialog text | 8 | 8 |
| `dashboard.php` | Owner, accountant and teacher dashboards, quick links, notices | 98 | 98 |
| `enums.php` | Labels for roles, student status, payment status, fee head type, attendance status, payment method | 28 | 28 |
| `exams.php` | Exams index, marks entry, flash messages | 23 | 23 |
| `fees.php` | Fee index and flash messages | 23 | 23 |
| `home.php` | Landing page (hero, features, modules, roles, call to action, footer) | 81 | 81 |
| `inventory.php` | Inventory title, index, item view, flash messages | 35 | 35 |
| `messages.php` | Shared UI words (save, cancel, search, columns, success and error messages) | 63 | 63 |
| `notices.php` | Notices index and flash messages | 21 | 21 |
| `pagination.php` | Previous and next links of Laravel's paginator (en only) | 2 | missing |
| `passwords.php` | Laravel password reset messages (en only) | 5 | missing |
| `payroll.php` | Payroll index, payslip view, flash messages | 43 | 43 |
| `portal.php` | Parent portal pages (my children, child view) | 23 | 23 |
| `staff.php` | Staff index and form | 34 | 34 |
| `students.php` | Admission and profile forms (fields, hints, placeholders), religions, genders, relations, errors | 94 | 94 |
| `transport.php` | Transport routes, vehicles, print view, form labels, errors | 67 | 67 |
| `validation.php` | Laravel validation messages and attribute names | 138 | 22 |

## Enum labels

Enum `label()` methods return translated text. Stored values stay English identifiers (for example `present`), and only the label is shown.

| Enum (`app/Support/Enums`) | Label key |
|---|---|
| `UserRole` | `enums.roles.{value}` |
| `StudentStatus` | `enums.student_status.{value}` |
| `PaymentStatus` | `enums.payment_status.{value}` |
| `FeeHeadType` | `enums.fee_head_type.{value}` |
| `AttendanceStatus` | `enums.attendance_status.{value}` |
| `PaymentMethod` | `enums.payment_method.{value}` |
| `Religion` | `students.religions.{value}` |
| `StockMovementType` | Not translated from a file: `label()` returns fixed text in both languages, for example `স্টকে যোগ / Stock in` |

## Formatting with LocaleFormat

`App\Support\Helpers\LocaleFormat` formats values for display only and never changes stored data. Each method checks whether the active locale is `bn`.

| Method | Example input | `en` output | `bn` output |
|---|---|---|---|
| `digits($value)` | `2026` | `2026` | `২০২৬` |
| `number($value, $decimals = 0)` | `1234` | `1,234` | `১,২৩৪` |
| `taka($amount, $isPoisha = true)` | `123450` (poisha) | `৳1,234.50` | `৳১,২৩৪.৫০` |
| `date($value)` | `2026-11-15` | `15 Nov 2026` | `১৫ নভেম্বর, ২০২৬` |
| `monthName($month)` | `11` | `November` | `নভেম্বর` |
| `dayName($value)` | a date | English weekday (`l` format) | Bangla weekday from `BanglaHelper` |
| `mobile($value)` | `01XXXXXXXXX` | `01XXXXXXXXX` | `০১XXXXXXXXX` |

Notes:

- `taka()` treats amounts as poisha by default (100 poisha is 1 taka). Pass `false` as the second argument for taka amounts.
- `date()` returns an empty string for `null` or an empty value.
- Views call `digits()` (8 files), `number()` (3 files), `taka()` (12 files) and `date()` (5 files). `monthName()`, `dayName()` and `mobile()` have no callers in `app/` or `resources/views/`; they are covered by `tests/Unit/LocaleFormatTest.php` only.
- Money in views goes through `taka()`, so amounts are stored in poisha and shown with the taka sign and two decimals.

## Bangla digit input

Users can type Bangla digits in numeric, date and mobile fields. Form requests normalise input before validation runs:

- `App\Http\Requests\Concerns\NormalizesInput` is a trait. Its `normalizeInput(textFields, digitFields, mobileFields)` method is called from `prepareForValidation()` in 21 form requests (for example `StoreStudentRequest`, `CollectFeeRequest` and `StoreAttendanceRequest`).
- Text fields: control characters are removed (tab, newline, carriage return, ZWJ and ZWNJ are kept), the text is normalised to Unicode NFC, and it is trimmed.
- Digit fields: Bangla digits become ASCII digits through `BanglaHelper::toEnglishDigits()`, after trimming.
- Mobile fields: spaces, hyphens, brackets and dots are removed, digits are converted, and a leading `+88` or `88` is stripped. A value matching `01[3-9]` followed by eight digits becomes the canonical `01XXXXXXXXX` form. Anything else is returned as digits only and left for validation to reject.
- `App\Support\Helpers\NormalizesInputHelper` exposes the same methods statically for code that is not a form request: the `InstallKcam` console command, `AuthenticatedSessionController` and `RegisteredSchoolController`.

`App\Support\Helpers\BanglaHelper` holds the character tables and the low-level helpers: `toBanglaDigits()`, `toEnglishDigits()`, `dayName()` and `monthName()`. See Limitations for the helpers that are not used.

## Fonts

| Font | Loaded from | Used for |
|---|---|---|
| Inter | Google Fonts stylesheet in `components/app-layout.blade.php` and `components/guest-layout.blade.php` (weights 400, 500, 600 and 700, `display=swap`) | English text (first in the English stack) |
| Hind Siliguri | Same Google Fonts stylesheet (weights 400, 500, 600 and 700) | Bangla text (first in the Bangla stack) |
| Noto Sans Bengali | Google Fonts stylesheet in `accounts/cash-book.blade.php` only (weights 400, 600 and 700). Elsewhere it is used only when the device has it installed | Fallback in both stacks |

Kalpurush was removed from the stacks because its single Regular file was declared for every weight, so bold text looked too heavy. The file `public/fonts/kalpurush.ttf` is still in the repository but nothing loads it.

Font stacks in `resources/css/app.css`:

- English (`@theme`): `'Inter', 'Hind Siliguri', 'Noto Sans Bengali', system-ui, -apple-system, sans-serif`.
- Bangla (`:root:lang(bn)`): `'Hind Siliguri', 'Noto Sans Bengali', 'Inter', system-ui, sans-serif`.
- `--font-bn` is declared in `@theme`, but no view uses it.

Content Security Policy (`app/Http/Middleware/SecurityHeaders.php`): `style-src` allows `https://fonts.googleapis.com` and `font-src` allows `https://fonts.gstatic.com`. A new font host needs both the stylesheet link and a CSP change. In the local environment the policy is sent as `Content-Security-Policy-Report-Only`.

Two pages differ from the shared setup:

- The landing page (`resources/views/welcome.blade.php`) has no Google Fonts link.
- The cash book (`resources/views/accounts/cash-book.blade.php`) loads Noto Sans Bengali from Google Fonts and sets its own body font, which is outside the token stack.

## Adding text in both languages

1. Pick the file by area from the table in Language files (for example, fee screens use `fees.php`).
2. Add the same key path to `lang/en/<file>.php` and to `lang/bn/<file>.php`, with identical nesting.
3. In Blade and PHP, reference the key, never the English text: `{{ __('fees.index.title') }}`.
4. Show numbers, money and dates through `LocaleFormat`. Do not concatenate raw values or hard-code digits.
5. Enum labels go in the enum group of `lang/en/enums.php` and `lang/bn/enums.php`.
6. Validation messages go in both `lang/en/validation.php` and `lang/bn/validation.php`. Without a Bangla entry the English message shows on Bangla pages.
7. Compare the key sets of the two files before merging. No automated check exists (see Limitations).
8. Run the locale tests:

```bash
php artisan test --compact tests/Feature/LocaleSwitchTest.php
php artisan test --compact tests/Unit/LocaleFormatTest.php
```

## Limitations

- `lang/bn/validation.php` has 22 of the 138 keys in `lang/en/validation.php`. The other validation messages show in English on Bangla pages because `APP_FALLBACK_LOCALE` is `en`.
- `lang/bn/pagination.php` and `lang/bn/passwords.php` do not exist. Paginator "Previous" and "Next" links and password reset messages show in English on Bangla pages.
- Inventory stock errors are English. `RecordStockMovementAction` throws `Quantity must be a non-zero whole number, positive for in and out.`, and `InsufficientStockException::for()` builds `Only N in stock, cannot remove M.`. The controller embeds that text in the Bangla message `inventory.flash.insufficient_stock` (`lang/bn/inventory.php`, line 45), so the detail stays English.
- `StockMovementType::label()` hard-codes bilingual text inside the enum instead of using a language file.
- Hard-coded English text remains in components: the modal close button's screen-reader label is `Close`, and the switcher's group label is `Language / ভাষা`.
- The language choice lives only in the session. There is no saved preference per user.
- Dates are Gregorian only. No Bangla (Bengali) calendar conversion exists.
- `BanglaHelper::formatTaka()` defaults to taka (`$isPoisha = false`), while `LocaleFormat::taka()` defaults to poisha. `BanglaHelper::formatTaka()`, `formatDate()` and `ordinal()` are not called from `app/` or `resources/views/`. `ordinal()` appends `ম` in every case; its code comment calls it a simplification.
- `LocaleFormat::monthName()`, `dayName()` and `mobile()` have no production callers (see Formatting).
- No automated test checks that `en` and `bn` key sets match.
- Hind Siliguri is loaded from Google Fonts only on the app and guest layouts. The landing page (`welcome.blade.php`) has no font link, so its Bangla text falls back to Noto Sans Bengali when the device has it, and otherwise to the system Bangla font.
- Each page that loads a Google Fonts stylesheet depends on Google being reachable. Self-hosting the Bangla font would remove that dependency; it is not done.
