# 13 — Debugging playbook

Debug one request at a time. Write down the method, the path, the role, and the status code before you change code.

## A full trace: patient opens a visit

1. Browser: `GET /patient/bookings/15`.
2. `php artisan route:list --path=patient/bookings` shows `patient.bookings.show` → `Patient\BookingController@show`, middleware `web`, `auth`, `role:patient`.
3. If the response is a redirect to `/login`, the session cookie was missing. Check the `sessions` table and that the request host matches `SESSION_DOMAIN`.
4. If the response is 403 with the role sentence, `users.role` is not `patient`.
5. If the response is 403 without that sentence, the method’s `patient_id` check failed. The id in the URL is someone else’s visit.
6. If the response is 404, appointment 15 does not exist.
7. If the response is 200 but the page is blank, the view `dashboard.appointments.show` rendered and the partial hid the body with `@if`. Read `patient-visit.blade.php`.
8. If the response is 500 and names an undefined variable, the controller’s `return view` array omitted it.

## Symptom guide

### Route not found (404) and you expected a page

**Meaning:** No route matched method and path.

**Causes:** Typo, POST sent to a GET-only URL, `{slug}` eating a path you thought was static, or the site root is not `public/`.

**Look at:** `routes/web.php`, `php artisan route:list --path=the/path`.

**Do not:** add a duplicate route above a more specific one without checking order. `patients/create` must stay before `patients/{patient}`.

### Wrong route name

**Meaning:** `route('patient.profile')` throws. The patient profile name is `patient.profile.show`.

**Look at:** the name column in [chapter 03](03-complete-route-registry.md).

### Middleware redirect loop

**Meaning:** Login sends you to dashboard, dashboard sends you to login.

**Causes:** session not saved (database session table missing, or `migrate` never run on a new database). This machine’s migrations are Ran, so a loop here is more likely a cookie domain or `APP_URL` mismatch.

### Login fails with a correct password

**Causes:** email typo, rate limit (5 attempts), password was changed, `APP_KEY` changed so old hashes still work but cookies do not. `is_active` is not a cause today, because login ignores it.

**Look at:** `LoginRequest`, `storage/logs/laravel.log`.

### Wrong-role 403

**Meaning:** `EnsureUserHasRole` failed.

**Look at:** `users.role` for that email. Registration cannot create `admin`.

### Validation error you cannot see

**Causes:** the error bag is not `default` (`updatePassword`, `userDeletion`). The Blade file reads the wrong bag. Or the code used `abort(422)` instead of `ValidationException`, so there is no field error.

**Look at:** [chapter 11](11-middleware-errors-and-responses.md).

### Undefined variable in a view

**Meaning:** the template prints `$foo` and the controller did not pass `foo`.

**Look at:** the `return view(` line and the template’s variables.

### Undefined method on a model

**Meaning:** the relationship or helper is not on that class. `Doctor` name access goes through `getAttribute` to `user`. A missing `user()` relation would error when the override calls `$this->user`.

### Empty list

**Causes:** filter too tight, `approval_status` not `approved`, `is_active` false, wrong `doctor_id`, or the date scope `upcoming` hides past times. Timezone is UTC, so “today” may not match your clock.

### SQL error about `active_slot` or duplicate email

**Meaning:** a unique index fired. Two bookings collided, or the email exists.

**Do not:** drop the unique index to “make the form work”.

### Broken form / 419

**Meaning:** CSRF token missing or the session expired.

**Look at:** `@csrf` in the form, meta tag for JavaScript, and whether the route is the webhook exception by mistake.

### File upload failure

**Causes:** PHP `upload_max_filesize` and `post_max_size` (framework `ValidatePostSize` runs first), mime not in the list, 10 MB vs 5 MB depending on the form, cancelled visit, or 24-report cap.

**Look at:** `StorePatientAppointmentRequest` versus `StoreAppointmentReportsRequest`.

### Vite / unstyled page

**Causes:** `npm run dev` is not running and `public/build` is missing. Tests hide this with `withoutVite()`. A real browser does not.

**Look at:** the page source for `@vite` output. `public/hot` means a dev server was expected.

### JavaScript widget does nothing

**Causes:** Alpine failed to start because the bundle 404s, or a POST returned 419. Open the browser network tab. This audit did not click the widgets.

### Wrong redirect

**Causes:** `back()` used the Referer. A form opened from `/` returns to `/`. The doctor profile test failed this way after validation: the location was `http://localhost`, not the profile page. If you need a stable target, redirect to a named route instead of `back()`.

**Look at:** the controller’s `return redirect` and the test’s `assertRedirect`.

### Status will not change

**Causes:** `applyStatus` rejected the transition, the receptionist list omitted `completed`, or the visit is already `completed` or `cancelled`.

**Look at:** the current `appointments.status` value and the table in [chapter 09](09-business-workflows.md).

### 500 after a successful-looking form

**Causes:** a service threw after validation. Read the log line’s exception class. A unique constraint is often a `QueryException`.

## Safe commands

| Command | Effect |
|---|---|
| `php artisan route:list --name=patient` | Read-only |
| `php artisan about` | Read-only |
| `php artisan migrate:status` | Read-only |
| `php artisan test tests/Feature/SomeTest.php` | Uses memory SQLite |
| `php artisan view:clear` | Deletes compiled views. Safe. Use it when a template change does not appear |

**Do not run** `migrate:fresh`, `db:wipe`, or `db:seed` on the MySQL database unless you intend to destroy clinic data.

## What not to do

- Do not turn off CSRF to fix a 419.
- Do not log report text or passwords while debugging.
- Do not use `APP_DEBUG=true` on a shared server.
- Do not “fix” authorization by removing `abort_unless`.

## Related

[02-request-lifecycle.md](02-request-lifecycle.md)
