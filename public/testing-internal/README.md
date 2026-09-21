# PHP SPA Template — Login / Signup / Dashboard / Admin Dashboard

A minimal, framework-free single-page application: plain PHP for a JSON API,
vanilla JS for a hash-routed frontend, MySQL for storage, Bootstrap for styling.

## Structure

```
config/database.php   PDO connection settings
sql/schema.sql         users table
includes/functions.php session, JSON, CSRF, and auth-guard helpers
api/auth.php            signup, login, logout, me, csrf-token
api/dashboard.php       example authenticated endpoint
api/admin.php           example admin-only endpoint (list/edit/delete users)
index.php               single HTML entry point
assets/js/app.js        router + views (home, login, signup, dashboard, admin)
assets/css/style.css    small tweaks on top of Bootstrap
```

## Setup

1. Create a database and run the schema:
   ```
   mysql -u root -p -e "CREATE DATABASE php_spa_template"
   mysql -u root -p php_spa_template < sql/schema.sql
   ```
2. Edit `config/database.php` with your DB host/user/password.
3. Point a PHP web server at this folder, e.g.:
   ```
   php -S localhost:8000
   ```
4. Visit `http://localhost:8000`, sign up, then promote yourself to admin:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'you@example.com';
   ```

## Security notes (please read before deploying)

This template covers the basics correctly, but a few things are on you before
it goes anywhere public:

- **HTTPS.** Cookies are set `httponly` + `SameSite=Lax` but not `secure`.
  Once you're on HTTPS, uncomment `'secure' => true` in
  `includes/functions.php` so session cookies never travel unencrypted.
- **Rate limiting.** There's no throttling on `/login` or `/signup`, so
  nothing stops brute-force or account-enumeration attempts at the
  infrastructure level. Add rate limiting at the reverse proxy (e.g. nginx
  `limit_req`) or track failed attempts per IP/email in the database.
- **CSRF token storage.** The token lives in the session and is sent back via
  a header, which is the standard double-submit-adjacent pattern for JSON
  APIs — but it does mean a page-load is required before any POST can
  succeed. That's already handled by `loadSession()` in `app.js`.
- **Admin self-lockout.** `admin.php` blocks an admin from demoting or
  deleting their own account through the API, but nothing stops it via direct
  DB access — worth keeping in mind if you script account management.
- **Error detail.** `config/database.php` returns a generic message on
  connection failure rather than the PDO exception text, so you don't leak
  DB host/credentials in a stack trace. Keep that pattern if you add more
  files that touch the DB directly.
- **Input beyond email/password.** Only signup fields are validated here. Any
  new field you add to forms needs the same treatment: validate server-side
  (never trust client-side `required` alone), and use prepared statements
  (already the default via PDO here) for anything that touches SQL.

## A couple of PHP/JS features worth knowing about

- **`password_hash()` / `password_verify()`** — used for storing and checking
  passwords. Never store or compare raw/SHA1/MD5 passwords; these functions
  handle salting and a modern algorithm (bcrypt by default) for you.
- **PDO prepared statements** (`$pdo->prepare(...)->execute([...])`) — the
  `?` placeholders keep user input out of the SQL string entirely, which is
  what prevents SQL injection. Never concatenate user input into a query.
- **`hash_equals()`** — used for the CSRF check instead of `===`. It runs in
  constant time, so it doesn't leak how many characters matched via response
  timing.
- **Hash-based routing (`location.hash`)** — lets the whole app live on one
  PHP page (`index.php`) with no server-side route config, since the part
  after `#` never gets sent to the server. Good fit for a lightweight SPA
  like this one; for SEO-sensitive public pages you'd want server-rendered
  routes instead.
