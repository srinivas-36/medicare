# 11 — Middleware, exceptions, sessions, and responses

## Global middleware

From Laravel 12.69.3 `Middleware::getGlobalMiddleware`, unchanged by this app:

1. `ValidatePathEncoding`
2. `InvokeDeferredCallbacks`
3. `TrustProxies`
4. `HandleCors`
5. `PreventRequestsDuringMaintenance`
6. `ValidatePostSize`
7. `TrimStrings` — strips whitespace around strings. It does not remove all-space strings inside a field in every case; `required` still rejects an empty string after trim.
8. `ConvertEmptyStringsToNull` — `""` becomes `null`, so `nullable` rules apply.

## `web` group

1. `EncryptCookies`
2. `AddQueuedCookiesToResponse`
3. `StartSession`
4. `ShareErrorsFromSession`
5. `ValidateCsrfToken` except `stripe/webhook`
6. `SubstituteBindings`

Then route middleware: `auth`, `guest`, `role`, `signed`, `throttle`.

## `api` group

`EnsureFrontendRequestsAreStateful`, then `SubstituteBindings`. `throttleApi()` is not called.

## Custom middleware

| Class | Registered? | Behavior |
|---|---|---|
| `App\Http\Middleware\EnsureUserHasRole` | Alias `role` | 403 unless `users.role` is one of the parameters |
| `App\Http\Middleware\RoleMiddleware` | No | Returns the next response immediately. Dead code |

## Built-in aliases this app uses

| Alias | Class | Effect |
|---|---|---|
| `auth` | `Authenticate` | Guest → redirect `login` |
| `guest` | `RedirectIfAuthenticated` | Logged-in user leaves login and register |
| `signed` | `ValidateSignature` | Email verification link |
| `throttle` | `ThrottleRequests` | 429 when the limit is exceeded |
| `auth:sanctum` | Sanctum | `/api/user` |

Aliases defined by the framework but **not used** on clinic routes: `verified`, `password.confirm`, `can`, `auth.session`.

`can` would call policies through Gate. This app calls `AiAnalysisPolicy` manually instead.

## How failures become responses

| Failure | Exception or abort | Browser result |
|---|---|---|
| Not logged in | `AuthenticationException` | 302 to `/login` |
| Wrong role or `abort(403)` | `HttpException` 403 | 403 page. Role middleware message: “You are not authorized to access this page.” |
| Form request `authorize()` false | `AuthorizationException` | 403 |
| Validation | `ValidationException` | 302 back with errors, or 422 JSON |
| Missing model | `ModelNotFoundException` | 404 |
| `abort(404)` | `HttpException` 404 | 404 |
| `abort(422)` | `HttpException` 422 | 422 page, not the form error bag |
| Bad CSRF | `TokenMismatchException` | 419 |
| Bad signed URL | signature middleware | 403 |
| Throttle | `ThrottleRequestsException` | 429 |
| Any other throw | `Throwable` | 500. Debug page if `APP_DEBUG=true` |

`bootstrap/app.php` leaves `withExceptions` empty. Rendering is the framework default. There is no `resources/views/errors` override.

`abort(422)` does **not** redirect back with `@error`. Patient reschedule uses `abort_unless($appointment->canPatientCancel(), 422)`. The person sees Laravel’s 422 page, not a field error. Status changes that throw `ValidationException::withMessages` do redirect back with a field error. The two styles feel different. Both are used on purpose in the current code.

## Redirects

`redirect()->route('name')` is 302. `back()` uses the `Referer` header or the session’s previous URL. `with('status', '...')` flashes a string for one request. `withErrors` flashes the error bag. `withInput` is automatic on validation redirects.

`/dashboard` redirects again based on role. A patient never stays on `/dashboard`.

Login may redirect to `url.intended`, which the booking draft sets to the doctor book page.

## JSON

Review index, patient search, message index, analysis status, and referral preview return JSON. They still pass through `web` middleware, including CSRF on POST. A `fetch` POST without the token is 419 even if the JavaScript looks correct.

## Sessions

`StartSession` loads the row. Login stores the user id. Logout deletes the session data and rotates the CSRF token so a stolen form token stops working.

Flash keys used in controllers include `status` (`Profile saved.`, `Message sent.`, `Appointment rescheduled.`, `Doctor approved.`, `Password updated.`). Breeze uses `profile-updated` and `password-updated`. The Blade file must look for the key the controller sets. A mismatch looks like a silent save.

## Logging

Log channel is `stack` / `single`. `ClinicNotifier` imports `Log`. Password-reset mail is written to `storage/logs/laravel.log` when the mailer is `log`. That file can contain reset URLs. Do not commit it or paste it into this handbook.

`APP_DEBUG=true` shows stack traces. Turn it off on any machine other people can reach.

## Idempotency and retries

Stripe webhook: if `payment_status` is already `paid`, the handler returns. Safe to retry.

Referral accept: only from `sent`. A retry after success is 422.

Analysis store: a second request while one is open does not start another job (tested).

Booking: unique `active_slot`. A retry after success creates a second visit only if the first was cancelled (slot cleared) or the time differs.

The submit-button lock is not idempotency. Refreshing a POST result page can prompt the browser to resubmit. Redirect-after-POST, which these controllers use, avoids that on a normal navigation.

## Related

[02-request-lifecycle.md](02-request-lifecycle.md), [13-debugging-playbook.md](13-debugging-playbook.md).
