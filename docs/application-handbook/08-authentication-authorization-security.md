# 08 — Authentication, authorization, and security

Four different questions:

| Question | Medicare mechanism |
|---|---|
| Who is logged in? | Session guard `web`, `users` table |
| What role are they? | `users.role` plus `EnsureUserHasRole` |
| Do they own this row? | `abort_unless` on `patient_id` or `doctor_id`, plus a few scoped bindings |
| Is the clinic state valid? | `applyStatus`, `canRefer`, `canPatientCancel`, `AppointmentBooking`, unique `active_slot` |

A hidden button is not authorization. A passed validator is not ownership. An ownership check is not a legal status transition.

## Registration and login

**Registration** (`RegisteredUserController::store`): only `patient` or `doctor`. Doctors start `pending`. Passwords use the `hashed` cast. A transaction covers the profile row. `RegistrationTest` rejects privileged roles.

**Login** (`LoginRequest::authenticate`): `Auth::attempt` with email and password. Remember-me is optional. Session id is regenerated in the session controller (Breeze `store`). Rate limit: 5 attempts per lowercase email plus IP, then `auth.throttle`.

**Logout:** `AuthenticatedSessionController::destroy` invalidates the session and regenerates the CSRF token.

**Password reset:** broker table `password_reset_tokens`, expire 60 minutes, throttle 60 seconds (`config/auth.php`). Mail driver is `log`, so the link is written to the log.

**Email verification:** routes exist. `User` does not implement `MustVerifyEmail`. `verified` middleware is not on clinic routes. Verification is optional.

**Confirm password:** routes exist. No clinic route uses the `password.confirm` middleware.

## Session and cookies

`php artisan about` reported session driver `database`. Rows live in `sessions` (id, user id, IP, user agent, payload, last activity). `.env.example` sets `SESSION_ENCRYPT=false`. The session cookie is still encrypted by `EncryptCookies`. The payload inside the database table is not encrypted when that flag is false. Anyone who can read the database can read flashed form input stored in session payloads. Do not put report contents in the session. The booking draft stores form fields and stashed file paths; treat that as sensitive.

`SESSION_LIFETIME=120` minutes in the example file.

## Guards

Only the `web` session guard is defined. `GET /api/user` uses `auth:sanctum`. Sanctum’s stateful middleware is enabled. There is no separate API token UI in the Blade app. `HasApiTokens` is on `User`, so tokens can be created in code. No route in `routes/web.php` issues tokens.

## Role middleware

```23:27:app/Http/Middleware/EnsureUserHasRole.php
        abort_unless(
            $user && in_array($user->role, $roles, true),
            403,
            'You are not authorized to access this page.'
        );
```

Comparison is strict. The string must match the column exactly.

`RoleMiddleware` does not check a role. It is not aliased. Do not register it under the name `role`.

## Permission matrix

“Own” means the controller compares ids. “Assigned” means the receptionist’s `receptionists.doctor_id`.

| Action | Guest | Patient | Doctor | Receptionist | Admin |
|---|---|---|---|---|---|
| View marketing pages | yes | yes | yes | yes | yes |
| Register | yes | guest middleware blocks | same | same | same |
| Book on the public form | draft only | yes, if doctor approved and active | no | no | no |
| See own visits | no | yes | no | no | no |
| See a doctor’s visits | no | no | own `doctor_id` | assigned doctor | no dedicated UI |
| Change visit status | no | cancel/reschedule only, with rules | yes, with `applyStatus` | yes, except `completed` in validation | no |
| Upload/download reports on a visit | no | own visit | own visit | not a report route in the receptionist group | no |
| Messages on a visit | no | own | own | no | no |
| Refer | no | no | own visit and `canRefer()` | no | no |
| Accept referral | no | no | recipient, status `sent` | no | no |
| Add receptionist | no | no | own practice | no | no |
| Approve doctors | no | no | no | no | yes |
| Deactivate users | no | no | no | no | yes, with last-admin and self checks |
| Edit clinic settings | no | no | no | no | yes |
| Breeze `/profile` delete | no | yes, own account | yes, own account | yes | yes, own account |
| AI analysis | no | no | own visit | no | no |

Admin does not get a free pass into `/doctor/appointments/{id}`. The role middleware returns 403.

## Ownership examples

Patient visit (`app/Http/Controllers/Patient/BookingController.php`): `abort_unless($request->user()->id === $appointment->patient_id, 403)` on show, reports, messages, cancel, and reschedule.

Doctor visit (`Doctor\AppointmentController`): `abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403)`.

Receptionist (`Receptionist\AppointmentController`): same doctor id as the assignment, and a posted `doctor_id` that disagrees is 403.

Referral show: sender or recipient (`ReferralController::involved`). Accept and schedule: recipient only.

Report download on a referral: the report must be attached with `referral->reports()->whereKey($report->id)`.

Scoped route-model binding on patient report show/destroy and doctor appointment report show makes Laravel 404 when the report’s appointment does not match the URL. The controller still checks the user. Scoped binding is not a user-ownership check.

Doctor patient show: 404 unless the patient has a visit with this doctor or a `doctor_patients` row. A doctor cannot open an arbitrary user id.

## CSRF

`web` middleware validates the token. Blade `@csrf` prints it. The dashboard layout sets `<meta name="csrf-token">`. JavaScript that posts must send the token. Missing token: **419**.

Exception: `stripe/webhook`. `StripeWebhookController` rejects bad signatures with HTTP 400. Do not add other exceptions without a signature check.

## XSS

Blade `{{ }}` escapes HTML. Reviews and clinical notes printed with `{{ }}` are safe. `{!! !!}` would not be. Search before adding it. Alpine `x-text` also escapes. `x-html` does not.

## SQL injection

Eloquent and the query builder bind parameters. `LIKE` filters concatenate the user string into a pattern (`'%'.$term.'%'`), and the binding still escapes quotes. Wildcard characters `%` and `_` in the search box can widen the match. That is a search-quality issue, not classic injection.

Raw `selectRaw` in the admin dashboard groups by date. It does not concatenate request input into that SQL. The user search uses bindings.

## Mass assignment

`users.role` and `users.is_active` are fillable. Safe call sites pass an explicit array. `ProfileController::update` fills only validated name and email. Keep new endpoints on `validated()` or an explicit list.

`Appointment` is fillable for `status` and `patient_id`. Do not pass `$request->all()` to `Appointment::create`.

## File uploads

Booking files: mime list and 10 MB. Later uploads: 5 MB. Stored on the `local` disk (`storage/app/private`) with a UUID file name. The original extension is kept. Download routes check ownership before `MedicalFile::response`.

`MedicalFile::exists` returns true if the same relative path exists on the `public` disk. Downloads still require the controller’s ownership check. Do not move report paths onto the public disk.

`/storage/{path}` from Laravel’s `serve` option requires a valid signature for a private disk (`ServeFile::hasValidSignature`). Unsigned access is 403 when not in production and 404 in production.

## Stripe

Webhook checks signature, session id, amount in cents, and currency `usd`. A mismatch returns without updating. `lockForUpdate` avoids a double confirmation. The Checkout Session builder hard-codes `currency => 'usd'` even though `config('services.stripe.currency')` exists. If the config currency is changed, the webhook can reject payments that Stripe actually collected. That is a correctness bug with financial impact.

## Clinical text and the AI provider

`GenerateAiAnalysis` sends visit context and report extracts to the HTTP API in `config/services.php` (`ai.base_url`, `ai.key`). An empty key skips the call (tested). When a key is set, report contents leave the server. That is a privacy requirement to disclose and to gate, not a hidden feature.

`DoctorAnalysisWorkflowTest` failed because the referral page contained `Unapproved model summary.` The test expected that string to stay off the recipient’s screen until approval. That is a confirmed test failure about clinical-text exposure between doctors. Re-read `ReferralSender` and the referral Blade before shipping.

## `is_active` does not block login

Administrators set `users.is_active` in `Admin\DashboardController::updateUser`. Public catalogs and `AppointmentBooking` skip inactive doctors. `LoginRequest` and `Authenticate` do not read `is_active`. A deactivated doctor, patient, receptionist, or admin can still open their dashboard.

**Evidence:** no `is_active` check under `app/Http/Controllers/Auth`. Grep of `app/` shows the flag on catalogs, booking, referral validation, and the admin form only.

**Impact:** account deactivation does not end access.

**Fix direction (not applied):** reject inactive users inside `LoginRequest::authenticate` and on each request via middleware, and invalidate existing sessions when the flag flips.

## Account deletion

`ProfileController::destroy` (`routes/web.php` DELETE `/profile`) deletes the logged-in user after the current password. `users` deletion cascades doctors and appointments (`cascadeOnDelete`). Medical rows that point at those appointments cascade too. Any role that can log in can open this URL even if the dashboard menu hides it.

## Findings recorded for chapter 16

| Id | Topic | Severity |
|---|---|---|
| S1 | `is_active` ignored at login | High |
| S2 | `/profile` delete cascades clinical rows | High |
| S3 | AI provider receives report text when configured | High (privacy) |
| S4 | Failing test: unapproved summary visible on a referral | High until disproven |
| S5 | Stripe currency hard-coded `usd` | Medium |
| S6 | Email verification not enforced | Medium |
| S7 | `users.role` fillable | Medium (latent) |
| S8 | Session payload not encrypted | Medium |
| S9 | Public doctor profile does not require approval | Low |
| S10 | `RoleMiddleware` is a no-op | Low |
| S11 | `redirect()->to($url)` on notifications | Low while URLs come from `route()` |

No exploit was run against a real patient. Findings are from reading code and from the test suite.

## Related

[16-findings-and-recommendations.md](16-findings-and-recommendations.md)
