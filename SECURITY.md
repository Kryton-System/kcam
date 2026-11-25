# Security policy

## Supported versions

Security fixes go to the `main` branch. Older copies of the code, and installs that have not updated, are not patched separately.

## Reporting a vulnerability

Do not open a public issue or a pull request for a security problem. Send a private report to the Kryton System maintainers instead:

- Contact: [security contact to be added by the maintainers]

Include:

- what you found and where (route, screen, file or command)
- the steps to reproduce it, with test data only
- the impact you expect

## Sensitive data

KCAM stores personal data of students and guardians: names, dates of birth, guardian mobile numbers, BDRIS birth registration numbers, NID numbers, photos and NID scans. Do not put real records, scans or credentials in a report, an issue or a screenshot. Use the factories and test data instead.

## Built-in protections

- Each school's data is isolated by a global scope that fails closed.
- Routes check permissions with `can:` middleware, and records are checked against the signed-in school.
- Student photos and NID scans are stored in private storage and served only through authorized routes.
- Sign-in is throttled, forms are protected against CSRF, and security headers including a Content Security Policy are sent outside local development.

The current open items, including the pending CSP nonce work and owner verification, are listed in [docs/security-and-privacy.md](docs/security-and-privacy.md).
