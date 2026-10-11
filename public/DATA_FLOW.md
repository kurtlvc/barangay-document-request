# DokuBayan — How Data Moves

## Architecture

- Server-rendered PHP pages in `public/` (landing, login, resident/staff/admin dashboards).
- JSON APIs in `public/api/` (`auth.php`, `requests.php`, `documents.php`, `users.php`, `residents.php`, `profile.php`, `reports.php`, `dashboard.php`, `password-resets.php`).
- MySQL database `barangay_system` (`public/sql/schema.sql` plus `public/sql/migrations/`).
- Auth state lives in the PHP session (`bdr_start_session()`, `bdr_require_auth()`); write APIs additionally require a CSRF token (`bdr_require_write_csrf()`).

## Core tables

| Table | Role |
|---|---|
| `users` | Login accounts (`resident`, `staff`, `admin`); password hashes, security questions, `is_active` flag |
| `residents` | Resident profiles linked to `users.id`; `status` is `pending`, `verified`, or `rejected` |
| `document_types` | Requestable documents: fee, processing days, requirements, `is_active` |
| `requests` | One row per document request; carries `status`, `purpose`, `remarks`, `amount_due` (snapshot of the fee), `processed_by` |
| `request_history` | Append-only audit trail: every status change with actor (`updated_by`) and timestamp |
| `payments` | At most one row per request (`request_id` unique): cash payment with O.R. number and receiver |
| `password_resets` | Offline password-reset queue (`pending`, `approved`, `rejected`) with admin notes and resolver |

## Request lifecycle

Status path: `Pending` → `Processing` → `Ready for Release` → `Released`. Side exits: `Rejected` (staff, reason required), `Cancelled` (resident, only from `Pending`).

1. Resident submits (`POST api/requests.php`, no `action`): must have a verified resident profile. The API snapshots `document_types.fee` into `requests.amount_due`, inserts the `requests` row (`Pending`) and a `request_history` row in one transaction.
2. Staff processes (`POST api/requests.php` with `action=update-status`, or PATCH/PUT): sets `status`, `remarks`, `processed_by`, and appends a `request_history` row in one transaction. Cancelled requests can never be processed again.
3. Payment (`POST api/requests.php`, `action=mark-paid`, staff/admin only): allowed only when status is `Ready for Release` and `amount_due > 0`, and only once. Inserts into `payments` with the O.R. number and `received_by`.
4. Release gate: moving a request to `Released` while `amount_due > 0` requires an existing `payments` row, otherwise the API rejects with 422.
5. Resident self-service: `action=cancel` (only from `Pending`) and `action=update-purpose` (only from `Pending`), each ownership-checked against the session's `resident_id`.

Dashboards poll `api/dashboard.php`, which returns role-scoped stats and recent requests (resident: own requests; staff: pending queue; admin: global counts).

## Registration and verification

1. `POST api/auth.php` (`action=register`) creates a `users` row (resident role, hashed password, two hashed security-question answers).
2. The system looks for a walk-in `residents` row with matching first/last name and `user_id IS NULL`. Match → links it (`user_id` set, keeps its `verified` status). No match → creates a new `residents` row with `pending`.
3. The session is established immediately, but unverified residents are blocked from submitting requests (403) until staff verifies them (`api/residents.php` sets `residents.status`).

## Password recovery (no email/SMS)

- Self-service via security questions (`api/auth.php` `request-reset` / `verify-reset`): the client fetches the account's two questions, answers are compared against hashes, then the password is updated.
- Offline queue (`api/password-resets.php` → `PasswordResetService` → `PdoPasswordResetRepository`): a resident submits a reset request (one pending per user; unknown emails get a generic success to prevent enumeration). Admins approve (setting a new password, closing the queue row as `approved` with `resolved_by`, `admin_notes`, `resolved_at`) or reject it. Admins can also reset any user's password directly.

## Admin data management

- `api/documents.php`: CRUD on `document_types` (soft activate/deactivate via `is_active`).
- `api/users.php`: staff/admin account CRUD, `is_active` toggling.
- `api/residents.php`: verify/reject residents, edit linked `residents` + `users` rows.
- `api/reports.php`, dashboards, and staff/admin report pages aggregate `requests`, `payments`, `residents`, and `document_types`.

## Scenarios

### 1. Paid document, happy path
Juan (verified) requests a Barangay Business Permit (fee ₱150). `requests.amount_due = 150`, status `Pending`. Staff moves it `Pending` → `Processing` → `Ready for Release`. Juan pays cash at the hall; staff records the O.R. number (`payments` row, `amount = 150`). Staff sets `Released`. `request_history` holds all four transitions with actor names.

### 2. Free document
Clearance with fee ₱0 → `amount_due = 0`. The `mark-paid` endpoint refuses ("No payment is required"), and the release gate is skipped. `Released` succeeds with no `payments` row.

### 3. Rejection
Staff finds requirements incomplete → status `Rejected` with remarks ("Blurry ID photo"). The API rejects a rejection with empty remarks (422). The resident sees the reason in history and can submit a fresh request.

### 4. Resident cancellation
Maria submits a request by mistake while it is still `Pending` → `action=cancel` sets `Cancelled` plus a history row. Any later staff update attempt fails with 409.

### 5. Release blocked by unpaid fee
Staff tries to set `Released` on a ₱150 request with no `payments` row → 422 "Record the cash payment (O.R. number) before releasing". Double-payment attempts also fail with 409.

### 6. New registrant, no walk-in record
Ana registers; no matching `residents` row exists → new profile, `pending`. Login works, but submitting a request returns 403 until staff verifies her. Dashboard shows the pending-verification badge.

### 7. Walk-in record linking
The barangay previously encoded "Jose Rizal" as a resident with no account. Jose registers with the same name → his new `users.id` is written into the existing `residents` row, inheriting `verified`, and he can request immediately.

### 8. Forgotten password, self-service
Ramon answers his two security questions correctly → password updated directly, no staff involved.

### 9. Forgotten password, offline queue
Liza forgot her answers → submits a reset request with a reason, visits the hall with an ID. Admin verifies her in person, sets a new password, and the queue row closes as `approved` with `resolved_by` and `admin_notes`. A duplicate submission while one is pending returns the existing request instead of creating another.

### 10. Deactivated staff account
A former encoder's `users.is_active = 0` blocks login; their past `processed_by` attributions remain intact (`ON DELETE SET NULL`, never hard-deleted while referenced).
