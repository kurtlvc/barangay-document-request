# DokuBayan — Current Limitations

Known constraints of the system as it stands. Each item cites where it lives in the code.

## Scope and deployment

- **Single barangay, hardcoded identity.** Brgy. Mamatid name, hall address, and contact details are baked into pages (`index.php:145`, `index.php:153`, `login.php:38`). No `barangay_id` column exists on any table (`sql/schema.sql`), so multi-barangay tenancy is not possible without a schema change.
- **Database credentials hardcoded.** `config/database.php:4-7` pins host, database, user, and an empty password in source. No environment-variable or secrets-file support.
- **No migration runner or backups.** Schema is applied by hand (`sql/schema.sql`, `sql/migrations/`). No upgrade script, seed script for production, cron, or backup/restore tooling.
- **Landing page opens a DB connection for every visitor** (`index.php:4-6`), even unauthenticated ones.

## Authentication and session security

- **No remember-me.** Sessions are cookie-lifetime only (`includes/functions.php:22-35`), so closing the browser logs the user out.
- **No login rate limiting or lockout.** The login endpoint (`api/auth.php:270-321`) can be hit unlimited times; only security-answer checks have a weak session-based throttle (`api/auth.php:235-239`) that resets when the session is cleared.
- **No idle timeout.** There is no `last_activity` check or absolute session lifetime; a session stays valid until the browser closes it.
- **`secure` cookie flag off.** `includes/functions.php:30` leaves `secure => true` commented out, so session cookies can travel over plain HTTP.
- **Weak password policy.** Minimum 8 characters only, enforced in `api/auth.php`, `api/users.php`, `api/profile.php`. No complexity, breach-list, history, or expiry rules.
- **Logout is a GET request** (`logout.php:1-6`) with no CSRF check, so it can be triggered by a cross-site link or image.

## Payments

- **Cash only.** `payments.method` is `ENUM('cash')` (`sql/schema.sql:97`), and `"cash"` is hardcoded in `api/requests.php:151`. No GCash, card, or online payment path.
- **One full payment per request, no corrections.** `request_id` is unique in `payments`, the amount must equal `amount_due`, and there is no void, refund, partial-payment, or change-due handling (`api/requests.php:119-159`).
- **No receipts beyond the O.R. number.** Only the O.R. string is stored; no printable/digital receipt view.

## Notifications

- **Notification UI is a stub.** `includes/navbar.php:5-28` renders from a `$notifications` variable that every page sets to `[]`; the panel always says "You have no notifications."
- **No email, SMS, or push.** No mailer or SMS client exists in the codebase, so status changes (`api/requests.php:188-215`) never notify the resident. Residents must revisit the dashboard to learn their request moved.

## Documents and file handling

- **No file uploads.** `document_types.file_path` is a free-text path (`sql/schema.sql:39`, `api/documents.php:41-63`); there is no `$_FILES` handling, MIME check, or size limit. Templates must be placed on the server by hand.
- **Residents cannot attach files.** New requests carry only purpose/remarks text (`resident/new-request.php:22-48`, `api/requests.php:161-186`); IDs or supporting documents are verified in person.

## Audit trail

- **Only request status changes are audited** (`request_history`). Logins, user CRUD (`api/users.php`), resident edits (`api/residents.php`), document-type changes (`api/documents.php`), payment recording, and admin password resets leave no audit record.

## Reporting and scale

- **Report endpoints have no filters or pagination.** `api/reports.php:9-12` always scans full tables; list endpoints (`api/requests.php:37-50`, `api/residents.php:16-28`) return everything with no `LIMIT`/`OFFSET`/search.
- **Admin reports aggregate in the browser.** `admin/reports.php:11-13` dumps all rows into hidden DOM nodes for client-side JS math, which degrades as data grows.
- **Hardcoded windows.** Staff reports fix a 5-month window (`staff/reports.php:19`), a hardcoded year in a caption (`staff/reports.php:100`), and dashboard limits of 5–6 rows (`api/dashboard.php`).

## Correctness risk

- **Status-update race.** The staff status path reads the row without `FOR UPDATE` (`api/requests.php:195`) before updating (`api/requests.php:205`), unlike the cancel/edit/pay paths that lock rows. Two staff acting on the same request simultaneously can interleave writes.
- **No idempotency on submit.** Double-clicking submit (or retrying) can create duplicate `requests` rows; no idempotency key exists (`api/requests.php:173-181`).

## Localization and access

- **English only.** `lang="en"` with no gettext/locale files (`page-layout/header.php:6`).
- **Philippine phone format assumed** (`login.php:113`), and there is no offline mode, PWA shell, or service worker.
