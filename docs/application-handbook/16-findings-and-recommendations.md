# 16 — Findings and recommendations

No application code was changed. Recommendations are not implemented.

Severity:

- **Critical** — immediate serious security or data-integrity risk. None was confirmed at that level. Private report downloads require ownership, and the storage serve route requires a signature.
- **High** — major security, privacy, or workflow failure.
- **Medium** — reliability or correctness that will confuse operators or lose data in a narrower case.
- **Low** — limited impact.

Confidence is High when the source line was read or a test failed in this run.

## Confirmed defects

### F1. Deactivating a user does not stop login

- **Severity:** High
- **Confidence:** High
- **Evidence:** `users.is_active` is updated in `app/Http/Controllers/Admin/DashboardController.php` `updateUser`. `app/Http/Requests/Auth/LoginRequest.php` `authenticate` calls `Auth::attempt` and does not read `is_active`. `EnsureUserHasRole` does not read it either. Grep of `app/` shows the flag on catalogs, booking, and referrals, not on the auth controllers.
- **Impact:** An administrator who deactivates a doctor, receptionist, patient, or another admin does not remove their access.
- **Remediation:** Check `is_active` during login and in middleware, and invalidate that user’s sessions when the flag becomes false. Add the test proposed in chapter 12.

### F2. Any logged-in user can delete their account and cascade clinical rows

- **Severity:** High
- **Confidence:** High
- **Evidence:** `DELETE /profile` → `app/Http/Controllers/ProfileController.php` `destroy`. `users` foreign keys use `cascadeOnDelete` for doctors and appointments (`database/migrations/0001` users table is the parent; doctor and appointment migrations cascade). There is no soft delete on `User`.
- **Impact:** A password confirmation is required, so this is not anonymous. A doctor or admin who knows their password can erase the account and the rows that cascade from it. The dashboard menu does not link here. The URL still works. `layouts/navigation.blade.php` links to the Breeze profile.
- **Remediation:** Remove or role-limit `profile.destroy`. Prefer deactivation. If deletion stays, block it when appointments or referrals exist, or soft-delete.

### F3. Referral page showed an unapproved model summary in tests

- **Severity:** High
- **Confidence:** High that the test failed. The product intent is taken from the assertion.
- **Evidence:** `php artisan test` failed `DoctorAnalysisWorkflowTest::test_doctor_review_and_approval_are_audited_and_do_not_change_the_visit_note` at `tests/Feature/DoctorAnalysisWorkflowTest.php` around the `assertDontSee('Unapproved model summary.')` on `doctor.referrals.show`.
- **Impact:** A receiving doctor may see model text the sending doctor has not approved.
- **Remediation:** Trace the referral snapshot in `ReferralSender` and `dashboard/doctor/referrals/show.blade.php`. Show only the clinician-approved summary. Re-run that test.

### F4. Five feature tests fail against the current UI and validation

- **Severity:** Medium for the three copy mismatches, High where F3 applies, Medium for the phone redirect.
- **Confidence:** High
- **Evidence:** Chapter 12. Phone rule is `regex:/^[0-9]{10}$/` in `UpdateDoctorProfileRequest`. The test posts `5551234`. The redirect was `http://localhost` instead of the profile URL, which means `back()` after a validation error followed a Referer of `/`.
- **Impact:** The suite cannot be used as a green gate. Some screens no longer contain the sentences tests expect.
- **Remediation:** Decide whether the Blade copy or the tests are the spec. Align phone examples with the 10-digit rule. Redirect validation failures to a named route when the previous URL is not the form.

## Confirmed risks

### F5. External AI receives report text when a key is set

- **Severity:** High for privacy
- **Confidence:** High
- **Evidence:** `config/services.php` `ai` key. `AiAnalysisController::store` dispatches `GenerateAiAnalysis` synchronously. Tests prove an empty key skips the call.
- **Impact:** Clinical file text leaves the server.
- **Remediation:** Document consent, keep the key empty until that is accepted, and log only status codes.

### F6. Stripe currency is hard-coded

- **Severity:** Medium
- **Confidence:** High
- **Evidence:** `app/Services/StripeCheckout.php` sets `'currency' => 'usd'`. The webhook compares to `usd`. `config/services.php` also has `stripe.currency` from `STRIPE_CURRENCY`.
- **Impact:** Changing the env currency does not change Checkout, and a mismatch would make the webhook ignore a real payment.
- **Remediation:** Use `config('services.stripe.currency')` in both places.

### F7. Email verification is optional

- **Severity:** Medium
- **Confidence:** High
- **Evidence:** `MustVerifyEmail` is commented out in `app/Models/User.php`. Clinic routes do not use `verified`.
- **Impact:** Anyone who can receive mail at an address they do not prove can book. Password reset still depends on the mailbox.
- **Remediation:** Implement the contract and add `verified` only if the product requires it. Say so in the registration copy either way.

### F8. `users.role` and `is_active` are mass-assignable

- **Severity:** Medium, latent
- **Confidence:** High
- **Evidence:** `app/Models/User.php` `$fillable`.
- **Impact:** No current `User::create($request->all())` was found. A later endpoint that fills request input could set `role` to `admin`.
- **Remediation:** Remove `role` and `is_active` from `$fillable` and set them with `forceFill` in the three trusted creators.

### F9. Session rows are not encrypted

- **Severity:** Medium
- **Confidence:** High for the example and the reported driver
- **Evidence:** `.env.example` `SESSION_ENCRYPT=false`. `about` shows the database session driver.
- **Impact:** Database readers can see session payloads, including booking drafts.
- **Remediation:** Set `SESSION_ENCRYPT=true` after reading the Laravel 12 upgrade note for existing sessions.

### F10. `dispatchSync` holds the HTTP request for the AI call

- **Severity:** Medium
- **Confidence:** High
- **Evidence:** `GenerateAiAnalysis::dispatchSync` in `AiAnalysisController`.
- **Impact:** A slow provider makes the doctor’s browser wait. PHP timeouts look like a failed analysis.
- **Remediation:** Dispatch to the queue and keep the status poll, which already exists.

### F11. Appointment ownership is copied instead of a policy

- **Severity:** Medium (maintainability, becomes a defect if a new method forgets the check)
- **Confidence:** High
- **Evidence:** Repeated `abort_unless` in patient, doctor, and receptionist appointment controllers. Only `AiAnalysisPolicy` exists.
- **Impact:** A new action can ship without the check. Scoped bindings are only on three report routes.
- **Remediation:** One appointment policy used by every visit method.

### F12. Practice pages have no sidebar link

- **Severity:** Low
- **Confidence:** High
- **Evidence:** Routes `doctor.consultations`, `doctor.services`, `doctor.reports`, `doctor.reviews`. `config/dashboard.php` doctor group does not list them.
- **Impact:** Doctors cannot discover the pages. Tests still request the URLs.
- **Remediation:** Add links or remove the routes.

### F13. `RoleMiddleware` is a no-op

- **Severity:** Low
- **Confidence:** High
- **Evidence:** `app/Http/Middleware/RoleMiddleware.php` returns `$next($request)`. It is not registered.
- **Impact:** None until someone aliases it as `role` and replaces `EnsureUserHasRole`.
- **Remediation:** Delete the file.

### F14. Public doctor profile does not require approval

- **Severity:** Low
- **Confidence:** High
- **Evidence:** `Public\DoctorController::show` looks up by slug only.
- **Impact:** A pending doctor’s profile is visible if the slug is known. Booking still requires approval.
- **Remediation:** Use the same approved-and-active filter as `index`, or show a “not yet available” state.

### F15. Age and phone rules differ by form

- **Severity:** Low
- **Confidence:** High
- **Evidence:** Registration age is a 2-digit string. Patient profile age is an integer 0–120. Doctor phone is 10 digits. Receptionist phone is max 30 with no digit rule. Doctor-created patients use a 2-digit age and a free-form phone.
- **Impact:** The failing profile test. Inconsistent data.
- **Remediation:** One shared rule set.

### F16. Notification read uses `redirect()->to`

- **Severity:** Low
- **Confidence:** Medium
- **Evidence:** `NotificationController::read`. Current `ClinicNotifier` URLs are `route()` results.
- **Impact:** If a notification payload ever stores an external URL, the browser will follow it.
- **Remediation:** Allow only relative paths or known route names.

### F17. Contact page does not submit a message

- **Severity:** Low
- **Confidence:** Medium
- **Evidence:** No contact POST in the 126-route list. Sidebar links to `route('contact')`.
- **Impact:** “Contact support” is informational.
- **Remediation:** Say that on the page, or add a real endpoint.

## Architectural notes (not defects by themselves)

- Business rules live in controllers and `AppointmentBooking`, not in a single domain layer. That is workable. New rules should go in the service that already owns slots, not a third copy.
- Two analysis jobs exist. Only `GenerateAiAnalysis` is on the HTTP path.
- `MockReferralDirectory` sits beside real referrals. Keep the preview URLs obviously non-clinical in the UI.
- Views are cached (`about`). Template edits can look “stuck” until `view:clear`.

## Related

[08-authentication-authorization-security.md](08-authentication-authorization-security.md), [12-testing-and-quality-assurance.md](12-testing-and-quality-assurance.md).
