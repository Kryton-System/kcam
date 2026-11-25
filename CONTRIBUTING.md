# Contributing to KCAM

KCAM is a private project of Kryton System. This guide covers how to change the code safely. Setup steps are in the [README](README.md) and [docs/getting-started.md](docs/getting-started.md).

## Workflow

1. Branch from `main` with a short name, for example `feat/attendance-export` or `fix/receipt-number-gap`.
2. Write a failing test first, then the smallest change that makes it pass.
3. Run the checks before you push:

   ```bash
   php artisan test --compact
   vendor/bin/pint --format agent
   ```

4. Keep each pull request to one area of change. Use the pull request template: say what changed, why, and how you tested it.
5. Update the matching guide in `docs/` when behavior changes.

## Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/): `feat`, `fix`, `refactor`, `test`, `docs`, `chore` or `perf`, then a short description in the present tense.

```text
fix(fees): issue receipt numbers under a row lock
```

## Code rules

- Every tenant table has a `school_id`. Queries outside HTTP run inside `CurrentSchool::runAs()`.
- Money is stored as whole poisha in integer columns. Never use floats. Use `App\Support\Money` for conversions.
- Validate input in Form Requests. Protect routes with `can:` middleware, and check school ownership of every record.
- Put every new user-facing string in both `lang/en` and `lang/bn`, with the same keys. See [docs/localization.md](docs/localization.md).
- Use the shared components and theme tokens. Corners are at most 8px, with no gradients or glass effects. See [docs/design-system.md](docs/design-system.md).
- Use factories for test data. Never commit `.env`, credentials, keys, or real student or guardian records.
- Do not use long dashes (the em dash or en dash characters) in code, comments, UI text or documentation. Use a hyphen, colon, pipe or comma.

## Ask before

- Changing a dependency in `composer.json` or `package.json`
- Adding or changing a migration that touches data already in use
- Deleting files, tables or stored uploads
- Changing security headers, authentication or permission rules

Describe the reason in the pull request so a reviewer can weigh it.
