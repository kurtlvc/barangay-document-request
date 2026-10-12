# DokuBayan — Backend Map

Full map of the server side: runtime, shared core, database, every API endpoint, page guards, the password-reset subsystem, and how the JavaScript clients call it. Paths are relative to `public/`.

## 1. Runtime and layout

- Plain PHP 8 with MySQL (PDO). No framework, no router, no ORM, no composer packages.
- Document root is `public/`. Each URL maps to one PHP file: pages render server-side HTML, `api/*.php` files return JSON.
- Static assets live in `assets/` (`css/`, `js/`, `fonts/`, `img/`). Logos and favicons live in `assets/img/`; a copy of `favicon.ico` stays at the web root so browsers that request `/favicon.ico` directly do not 404.
- `config/database.php` opens one global `$pdo` (exceptions on, associative fetch, native prepares). Credentials are hardcoded (`127.0.0.1:3306`, `barangay_system`, `root`, empty password).
- `sql/schema.sql` is the full schema plus seed rows. `sql/migrations/` holds additive ALTER/CREATE files applied by hand, oldest first. Every table is InnoDB with `utf8mb4` — required, because the host default engine may be MyISAM, which silently drops foreign-key definitions and then fails later table creation with errno 150.

## 2. Request lifecycle

1. Browser requests a page (e.g. `resident/dashboard.php`). The page requires `includes/page-guard.php` (role check) and `config/database.php`, queries what it needs for first paint, then includes `page-layout/header.php` (which pulls in `includes/sidebar.php` and `includes/navbar.php`) and `page-layout/footer.php` (Bootstrap bundle + `assets/js/script.js` + page scripts).
2. Interactive behavior runs through `fetch()` calls to `api/*.php` with `credentials: 'same-origin'` and an `X-CSRF-Token` header read from `<meta name="csrf-token">`.
3. API files exit via `bdr_json_response()`, always `{status: 'ok' | 'error' | 'warning', ...}` with a matching HTTP code (200/201, 400/401/403/404/405/409/422/429/500).

## 3. Shared core (`includes/functions.php`, `includes/page-guard.php`)

- `bdr_start_session()` starts the session with HttpOnly + `SameSite=Lax` cookies, lifetime zero (closing the browser logs out; no remember-me, no idle timeout). It also mints `$_SESSION['csrf_token']`.
- `bdr_require_csrf()` / `bdr_require_write_csrf()` reject POST/PUT/PATCH/DELETE without a matching `X-CSRF-Token` (403).
- `bdr_require_auth(?$roles)` requires a session, re-validates `users.is_active` from the database on every call (deactivated accounts die immediately), refreshes `$_SESSION['role'|'resident_id'|'resident_status']` from the DB, then optionally enforces roles (401/403).
- `bdr_require_page_role($allowed)` is the page equivalent: no session goes to `login.php`, inactive account is destroyed and sent to `login.php`, wrong role goes to `dashboard.php`.
- `bdr_request_data()` reads `$_POST` or a JSON body. `bdr_json_response()` emits JSON and exits. `bdr_base_path()` computes the relative path back to the web root so nested pages (`admin/`, `staff/`, `resident/`) can build correct links.
- `bdr_resident_code($id)` formats display IDs as `RES-0001` (zero-padded to 4). Raw integers stay in the database; formatting happens only at display. Request references follow the same idea (`REQ-0001`, built inline). Document codes (`DOC-001`) are stored, not formatted.
- `bdr_security_questions()` lists the five allowed recovery questions; `bdr_normalize_answer()` lowercases/trims answers before hashing so "Manila" and "manila" match.

## 4. Authentication and session model

- Login (`api/auth.php`, no `action`): looks up `users` by email with a LEFT JOIN to `residents`, requires `is_active = 1` and `password_verify()`. On success it regenerates the session id and stores `user_id`, `name`, `email`, `role`, `resident_id`, `resident_status`. Failure is a generic 401 (`Invalid email or password`) to avoid leaking which half was wrong. There is no rate limiting on login.
- Registration (`action=register`): validates names, email, PH mobile, address, 8+ char password, and two distinct security questions with answers. Inside one transaction it inserts the `users` row (resident role, hashed password, hashed answers), then links a walk-in `residents` row with matching first/last name and `user_id IS NULL` (inheriting its status, usually `verified`) or creates a fresh `pending` profile. The session starts immediately, but `pending` residents get 403 from request submission until staff verify them.
- Self-service password reset (`action=request-reset` / `action=confirm-reset`): step 1 returns the account's two question texts; step 2 verifies both answers against hashes and sets the new password. Answer checks are throttled per email in the session (5 tries per 15 minutes).
- `action=me` returns the current session user. `action=logout` destroys the session and mints a fresh CSRF token. `logout.php` is the page-level equivalent (GET, redirects to `login.php`).
- `login.php` redirects to `dashboard.php` when already logged in. `dashboard.php` is a pure role router (`admin`/`staff`/`resident` to their dashboards). `assets/js/login.js` posts the form, then redirects to `dashboard.php` on success.

## 5. Database

| Table | Purpose |
|---|---|
| `users` | Login accounts (`resident`, `staff`, `admin`). Password hash, `is_active` kill switch, two security question/answer-hash pairs. |
| `residents` | Resident profiles, `user_id` unique nullable (walk-ins exist without accounts). Status `pending`/`verified`/`rejected`. |
| `document_types` | Catalog: `document_number` (e.g. `DOC-001`, unique, server-assigned increment-only, immutable after creation), name, fee, processing days, requirements, `is_active`. |
| `requests` | One row per document request. `status`, `purpose`, `remarks`, `amount_due` (fee snapshot at submit time), `processed_by`. |
| `request_history` | Append-only audit of every status change with actor and timestamp. |
| `payments` | At most one row per request (`request_id` unique). Cash only, O.R. number, receiver. |
| `password_resets` | Offline reset queue (`pending`/`approved`/`rejected`) with reason, admin notes, resolver and timestamps. |

Key relations: `residents.user_id → users.id` (SET NULL), `requests.resident_id → residents` (RESTRICT), `requests.document_id → document_types` (RESTRICT), `requests.processed_by → users` (SET NULL), `request_history` cascades from `requests` and is restricted on `users`, `payments.request_id → requests` (CASCADE, unique).

Seed data: admin, staff01, and Juan Dela Cruz users (password `password` for all three); Juan linked as a verified resident plus walk-in Maria Santos (verified, no account); four document types (`DOC-001`–`DOC-004`, all fee 0); two pending requests and one history row.

## 6. API reference

All endpoints require a session (`bdr_require_auth`), and all writes require the CSRF header.

### `api/auth.php` — identity
Covered in section 4. Actions: `csrf-token`, `me`, `logout`, `register`, `request-reset`, `confirm-reset`, plus the bare login POST. Notable codes: 422 validation, 409 email taken, 401 bad credentials, 429 reset-answer throttle.

### `api/requests.php` — request lifecycle
- `GET`: single request (`?request_id=`) with full detail plus history, or the list (residents see only their own; staff/admin see all, newest first).
- `POST` submit (resident only, verified profile only): validates purpose and an active document type, snapshots the fee into `amount_due`, inserts the `Pending` row plus a history row in one transaction (201). Same-day duplicate guard: if the resident already has a non-cancelled, non-rejected request for the same document dated today, it returns 409 with `code: 'duplicate'` and a warning message instead of inserting; resubmitting with `confirmed=1` proceeds. The new-request page shows a warning alert with a "Submit anyway" button for this case.
- `POST action=cancel` (resident, own row, only from `Pending`): sets `Cancelled` plus history; locked with `SELECT ... FOR UPDATE`.
- `POST action=update-purpose` (resident, own row, only from `Pending`).
- `POST action=mark-paid` (staff/admin): only from `Ready for Release`, only when `amount_due > 0`, only once; records O.R. number and receiver.
- `POST action=update-status` or PATCH/PUT (staff/admin): the staff pipeline. Rejection requires remarks; `Released` with an unpaid `amount_due` is rejected (422) until payment exists; `Cancelled` rows can never be processed again (409). Status path: `Pending → Processing → Ready for Release → Released`, with side exits `Rejected` (staff) and `Cancelled` (resident).

### `api/users.php` — staff/admin accounts (admin only)
- `GET` lists staff/admin accounts with contact and active flag.
- `POST` creates staff/admin accounts (8+ char temporary password). `PATCH/PUT` edits or toggles `is_active` (`action=set-active`), with guards against deactivating or demoting yourself. `DELETE` removes accounts not referenced by history, and never yourself.

### `api/residents.php` — resident profiles
- `GET`: staff/admin see all (with emails), residents see only themselves, single-row mode via `?resident_id=`.
- Writes are admin-only: `action=set-status` flips `pending`/`verified`/`rejected`; PATCH edits the profile (name/address/contact plus linked user name/email); POST creates a pre-verified walk-in record.

### `api/documents.php` — catalog (reads open, writes admin-only)
- `GET` returns all types for admins, only active ones for everyone else.
- `POST` creates a type; the server assigns the next `document_number` itself (max suffix + 1, same prefix/width, `DOC-001` when empty) with one retry on unique-race, and returns it in the 201 payload. Client-sent numbers are ignored.
- `PUT/PATCH` edits everything except the number: changing `document_number` is rejected with 422. Toggle path `action=set-active`; DELETE is blocked (409) while requests reference the type.

### `api/password-resets.php` — offline reset queue
- `POST ?action=request` is public (CSRF still required): any resident submits an email + reason. Unknown emails get a generic success so accounts cannot be enumerated.
- Everything else is admin-only (staff can neither list nor mutate since the admin-only change): `GET` lists the queue optionally filtered by status; `action=generate-temp` mints a password; `action=admin-reset` sets a new password and closes the linked (or latest pending) queue row as approved; `action=reject` closes it as rejected. Logic lives in `PasswordResetService` over `PdoPasswordResetRepository` (see section 7).

### `api/dashboard.php` — role-scoped stats (GET, any role)
Residents get own counts plus 5 recent requests; staff get global counts plus the 6-row pending queue; admins get user/resident/request/document totals plus 6 recent rows. Counts are cast to int; response carries a timestamp. Polled by `assets/js/dashboard.js` with a refresh button.

### `api/reports.php` — aggregates (GET, staff/admin)
Full-table counts by request status, by document, by resident status, plus the 10 most recent requests. No filters or pagination — fine at current scale, listed risk at larger scale.

### `api/profile.php` — self profile
- `GET` returns the user plus linked resident row.
- PATCH/PUT/POST updates name/email, optionally password (requires correct current password), and optionally the resident profile. Changing a verified resident's name or address resets `residents.status` to `pending` and the session flag with it, forcing re-verification. Email collisions surface as 409.

## 7. Password-reset subsystem (the one namespaced corner)

Small service/repository/DTO slice under `includes/` with `App\` namespaces:
- `Enums/PasswordResetStatus.php`: `pending`/`approved`/`rejected`.
- `DTO/SubmitPasswordResetRequestDTO.php`: lowercased email + optional reason.
- `DTO/AdminResetPasswordDTO.php`: target user id, new password, optional queue row id and admin notes.
- `Repositories/PasswordResetRepositoryInterface.php` + `Repositories/PdoPasswordResetRepository.php`: user lookups, queue insert, pending lookup (one pending per user — resubmits return the existing row), joined queue listing, status close-out with resolver and timestamp.
- `Services/PasswordResetService.php`: `submitResetRequest()` (validation, anti-enumeration, dedupe), `adminResetPassword()` (hash, update, auto-close linked or latest pending request), `rejectRequest()`, plus `generateTempPassword()`.
- `tests/Unit/PasswordResetServiceTest.php` is a dependency-free CLI test (mock repositories, `php` run, no PHPUnit) covering invalid email, unknown user, dedupe, and admin reset.

## 8. Pages

- Public: `index.php` (landing, opens a DB connection even for visitors), `login.php` (login/register tabs, forgot-password modal), `privacy-policy.php`, `terms-of-use.php`, `logout.php`.
- `dashboard.php`: role router, nothing else.
- Resident: `resident/dashboard.php` (stats + verification banner showing the `RES-0001` code + recent requests with `REQ-0001` refs), `resident/new-request.php` (catalog select, fee/processing/requirements panel, duplicate-day warning), `resident/my-requests.php`, `resident/request-details.php` (cancel/edit-purpose while pending), `account-settings.php` (profile + password via `api/profile.php`; identity edits can trigger re-verification).
- Staff: `staff/dashboard.php`, `staff/process-requests.php`, `staff/view-details.php` (status moves, rejection reasons), `staff/release-management.php` (O.R. capture, release gate), `staff/reports.php` (stat cards, server-rendered SVG monthly line chart, by-type table, print button).
- Admin: `admin/dashboard.php`, `admin/user-management.php` (server-rendered table, mutations via `api/users.php`), `admin/resident-records.php` (verify/reject/edit/reset-password, `RES-0001` code under each email, search includes the code), `admin/document-types.php` (catalog with visible `DOC-` codes, auto-incrementing locked code field), `admin/password-resets.php` (admin-only queue with approve/reject modals), `admin/reports.php` (generate panel, metric cards, per-month SVG line charts for request/resident kinds, categorical table for documents, CSV export, print via Export PDF).

## 9. Frontend wiring (`assets/js/`)

- `login.js`: login submit, multi-step registration with PH-mobile and password-match checks, forgot-password modal (security questions, then offline queue fallback) — all against `api/auth.php` and `api/password-resets.php`.
- `new-request.js`: catalog detail panel, submit with `confirmed` flag handling for the 409 duplicate warning, success toast then redirect.
- `dashboard.js`: polls `api/dashboard.php`, renders stats and recent rows.
- `admin-pages.js`: one file for all admin/staff management tables — accounts, residents (status flips, edits, search including `RES-` codes), document types (auto code preview, locked code field, server-assigned number adoption), reports (date grouping, metric cards, line charts, CSV export, print header + title), password-reset queue.
- `account-settings.js`: profile/password forms, pending-verification banner states.
- `script.js` + `theme-init.js`: global theme toggle and shared bits, loaded on every page via the footer/header.

## 10. Cross-cutting behaviors

- Display codes: residents `RES-0001` (via `bdr_resident_code()`), requests `REQ-0001`, documents `DOC-001` (stored, server-incremented, immutable). Search boxes include these codes.
- Toasts are ephemeral per-action feedback only; there is no notification system, no mailer, no SMS, no queue worker. The navbar bell is a stub that always reports no notifications.
- Print: `@media print` hides sidebar, navbar, modals, toasts, and anything tagged `data-no-print`; `.print-only` headers appear only on paper. Export PDF sets the document title for the filename.
- Money is cash-only, full-amount, one payment per request, identified by O.R. number. Release is blocked while `amount_due > 0` with no payment row.
- Audit covers request status changes only (`request_history`). Account, catalog, payment, and reset actions leave no audit rows.
