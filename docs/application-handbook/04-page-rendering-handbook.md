# 04 — Page-by-page rendering handbook

This chapter traces every distinct HTML screen. JSON and file endpoints are listed at the end because they are not pages. Shared shells are explained once. Each page still has its own route, controller, and rules.

Layouts:

| Layout | Used by | What it adds |
|---|---|---|
| `resources/views/layouts/public.blade.php` | Marketing, login, register, booking | Public header, footer, `@vite` assets |
| `resources/views/layouts/dashboard.blade.php` | Role dashboards | Sidebar from `config/dashboard.php`, search, account menu, notification count, Alpine shell |
| `resources/views/layouts/app.blade.php` | Breeze `/profile` | `layouts.navigation` and a simple card |
| `resources/views/layouts/guest.blade.php` | Confirm-password and some Breeze auth partials if those views still extend it | Check the view’s `@extends` line. Login and register in this repo extend `layouts.public`, not the guest layout |

Dashboard pages set `@section('title')`. The layout prints `@yield('title', 'Dashboard') — Medicare` and `noindex`.

A control that only toggles Alpine state (sidebar collapse, a tab) does not call the server. Those are marked frontend-only.

## How a page is assembled

1. Route middleware runs.
2. The controller loads models.
3. `return view('name', [...])` chooses a Blade file.
4. `@extends` wraps it in a layout.
5. `@include` and `<x-...>` pull partials.
6. `{{ }}` escapes text. `{!! !!}` prints raw HTML and is the XSS-sensitive form.
7. The browser loads CSS and JS through `@vite`.

---

## Public home — `/`

- **Route:** `GET /` `home`. Closure in `routes/web.php` lines 31–36. No login.
- **Purpose:** Explain the clinic and show featured approved doctors and services.
- **Reached from:** Logo, bookmarks.
- **Backend:** `PublicCatalog::featuredDoctors()` and `PublicCatalog::services()`. No form validation.
- **View:** `public.home` extends `layouts.public`. Includes hero, trust, medical services, doctors, how-it-works, services, faq, security components.
- **Interactions:** Links to doctors, services, about, login, register. No POST on this page itself.
- **Rules:** Featured doctors are active and approved. The catalog class is the filter. Unapproved doctors do not appear.
- **Beginner:** Opening `/` is a GET. PHP builds HTML. Nothing is saved.
- **Analogy:** A clinic’s front window.
- **Debug:** If the doctor row is empty, check `approval_status` and `users.is_active`, not the Blade loop first.

## About, contact, FAQ, security

| Page | URL | Route | View |
|---|---|---|---|
| About | `/about` | `about` | `public.about` |
| Contact | `/contact` | `contact` | `public.contact` |
| FAQ | `/faq` | `faq` | `public.faq` |
| Security | `/security` | `security` | `public.security` |

All are `Route::view`. Middleware `web` only. No controller, no database. Contact is a static page. The sidebar “Contact support” link uses `route('contact')`. It does not send an email. If the contact template contains a form, confirm its `action` before assuming a message is stored. This audit did not find a contact-submission route in `route:list`.

## Services — `/services`

- **Route:** `services.index`. Closure.
- **View:** `public.services.index`.
- **Data:** `PublicCatalog::paginatedServices()`.
- **Test:** `PublicServicesTest` expects the home page to show three services and this page to list six. That locks the catalog size. If the test fails, the catalog data changed.

## Doctor directory — `/doctors`

- **Route:** `doctors.index`. `Public\DoctorController@index`.
- **Query:** `query` filters name or specialization with `LIKE`.
- **Data:** Approved doctors, active users, with qualifications and time slots. `paginate(9)`.
- **View:** `public.doctors.index`.
- **Interactions:** Search form GET. Each card links to `doctors.show` and book.
- **Rules:** Pending and `cancelled` doctors are excluded. Inactive users are excluded.

## Doctor profile — `/doctors/{slug}`

- **Route:** `doctors.show`. Parameter is a **slug string**, not the doctor id.
- **Lookup:** `Doctor::whereHas('user', slug)`. `firstOrFail` → 404 if the slug is unknown.
- **View:** `public.doctors.show`. Variables: `doctor`, `reviewState`.
- **Interactions:** Book button to `doctors.book` (numeric id). Review UI posts to `doctors.reviews.store` or calls `doctors.reviews.index` for JSON. `resources/js/doctor-reviews.js` registers the Alpine widget.
- **Rules:** This page can load a doctor who is not approved, because the query does not filter `approval_status`. Booking still refuses unapproved doctors later.
- **Debug:** A 404 here means no user with that slug, not a missing doctor id.

## Public booking form — `/doctors/{doctor}/book`

- **Route:** `doctors.book`. `Public\BookingController@create`. `{doctor}` is `doctors.id`.
- **Auth:** The form is visible to guests. Submit is not.
- **View:** `public.doctors.book` with `formData()`.
- **Interactions:**
  - Choosing a slot is frontend until submit.
  - Guest continue posts to `doctors.book.remember`, which stores the draft in the session and sends the person to login. `AuthenticationTest` and `AppointmentBookingTest` cover restoration.
  - Logged-in patient posts to `doctors.book.store`.
  - A doctor or receptionist who is logged in cannot use the patient store route (`role:patient`).
- **Rules:** Date window, open slot, approval, and active user are enforced in `AppointmentBooking`, not only by hiding buttons.
- **Success:** Redirect toward checkout or the success page. Free visits become `confirmed` without Stripe (`AppointmentBookingTest`).

## Checkout success — `/checkout/appointments/{appointment}/success`

- **Route:** `checkout.success`. Patient only.
- **Auth:** `abort_unless` the appointment’s `patient_id` is the current user. Another patient gets 403.
- **View:** `public.checkout.success`.
- **Rules:** Showing the page does not itself mark the visit paid. Payment is the webhook or the free-booking branch.

## Login — `/login`

- **GET** `login` → `AuthenticatedSessionController@create` → `auth.login`.
- **POST** same path, no route name, `AuthenticatedSessionController@store`.
- **Middleware:** `guest`. A logged-in user is redirected away.
- **Fields:** `email`, `password`, optional `remember`. Rules in `LoginRequest`. Rate limit 5.
- **Success:** Session regenerated, redirect to `dashboard` or back to the booking draft.
- **Failure:** Redirect back with `auth.failed` on `email`. No user id is revealed.
- **Inactive users:** `is_active` is not checked. Documented as a defect in chapter 16.

## Register — `/register`

- **GET** `auth.register`. **POST** `RegisteredUserController@store` with `RegisterRequest`.
- **Fields:** name, email, password confirmation, 10-digit phone, 2-digit age, role `patient|doctor`, gender, optional photo.
- **Writes:** `users` row, then `patient_profiles` or `doctors` with `approval_status = pending`. Transaction. Photo deleted if the transaction fails.
- **Success:** Logged in, redirect `dashboard`.
- **Security:** `admin` and `receptionist` are rejected by `Rule::in`. Covered by `RegistrationTest`.

## Forgot password — `/forgot-password`

- **GET** `password.request` view `auth.forgot-password`.
- **POST** `password.email`. Sends a reset notification. With the log mailer, read `storage/logs/laravel.log` for the link. Do not paste tokens into docs.

## Reset password — `/reset-password/{token}`

- **GET** `password.reset` view `auth.reset-password`.
- **POST** `/reset-password` `password.store`.
- Token is signed by Laravel’s password broker and expires in 60 minutes (`config/auth.php`).

## Verify email — `/verify-email`

- **View:** the Breeze verification prompt (`EmailVerificationPromptController`).
- **Verify link:** `GET /verify-email/{id}/{hash}` signed and throttled.
- **Resend:** `POST /email/verification-notification`.
- **Rule:** Dashboards do not use the `verified` middleware. Skipping verification still allows booking.

## Confirm password — `/confirm-password`

- **GET** `password.confirm`. **POST** unnamed.
- Used when a route has `password.confirm` middleware. No clinic route in `web.php` applies that middleware. The screen exists because Breeze registered it.

## Breeze profile — `/profile`

- **GET** `profile.edit` → `profile.edit` inside `layouts.app`. Any authenticated role.
- **PATCH** `profile.update` updates name and email. Changing email clears `email_verified_at`.
- **DELETE** `profile.destroy` requires current password, logs out, deletes the user. Database foreign keys cascade appointments and doctor rows. This is a real data-loss path. The main dashboard account menu links to role profiles, not this URL. `layouts/navigation.blade.php` still links to it.
- **Tests:** `ProfileTest` covers display, update, and delete.

## Patient dashboard — `/patient/dashboard`

- **Route:** `patient.dashboard`. `Patient\DashboardController@index`. Also `abort_unless` role patient.
- **View:** `dashboard.patient` extends dashboard layout.
- **Data:** The patient’s upcoming and recent visits, passed from the controller. Sidebar search posts GET to `patient.book`.
- **Links:** Find a doctor, bookings, history, profile.
- **Test drift:** `DashboardWorkspaceTest::test_patient_books_a_doctor_from_the_dashboard` failed in this audit while asserting copy on this page. The route itself returned HTML titled Overview. Treat the visible sentences as able to change without the route disappearing.

## Find a doctor — `/patient/book`

- **Route:** `patient.book`. `BookDoctorController@index`.
- **View:** `dashboard.patient.doctors`.
- **Rules:** Only approved, active doctors. Pagination is asserted as 12 per page by `DashboardWorkspaceTest::test_patient_doctor_directory_shows_twelve_doctors_per_page` (that test was not in the failure list).
- **Query `doctor`:** redirects to `patient.book.create`.

## Patient booking form — `/patient/book/{doctor}`

- **Route:** `patient.book.create`. View `dashboard.patient.book`.
- **Submit:** posts to `doctors.book.store`, the same store action as the public form.
- **Partial:** `dashboard/patient/partials/book-form.blade.php`.
- **Rules:** Same `StorePatientAppointmentRequest` and `AppointmentBooking` checks. A doctor who is not bookable gets 403 from `BookDoctorController`.

## My appointments — `/patient/bookings`

- **Route:** `patient.bookings`. `BookingController@index`.
- **View:** `dashboard.appointments.index` (shared with the doctor list; the controller passes a role context).
- **Filters:** status and search query parameters are read in the controller. `AppointmentPagesTest` covers filters and paging.
- **Rows link** to `patient.bookings.show`.

## Visit detail (patient) — `/patient/bookings/{appointment}`

- **Route:** `patient.bookings.show`.
- **Auth:** `patient_id` must match. Otherwise 403. Another patient’s id is an IDOR attempt and is rejected.
- **View:** `dashboard.appointments.show` with `appointments/partials/patient-visit.blade.php`.
- **Interactions:**
  - Upload reports → POST `patient.bookings.reports.store`. At least one file. Max 24 reports on the visit (`abort` 422). Cancelled visits cannot be uploaded (`status !== cancelled`).
  - Delete report → DELETE scoped route.
  - Download → GET scoped route, streamed, owner check, file must exist.
  - Message → POST `patient.bookings.messages.store`. JSON index at `messages.index`. Alpine: `resources/js/visit-chat.js`.
  - Cancel → POST `patient.bookings.cancel` if `canPatientCancel()` (status pending or confirmed, start in the future, and cancellation notice hours).
  - Reschedule → POST `patient.bookings.reschedule`.
- **Test:** message exchange test failed on the final page assertion (`assertSee` of the message text or chat labels). The POST assertions for “Message sent.” are earlier in the same test. Read the failure as a page-copy or render mismatch until re-run in isolation.

## Medical history — `/patient/history` and `/patient/history/{appointment}`

- **Routes:** `patient.history`, `patient.history.show`.
- **Views:** `dashboard.patient.history`, `dashboard.patient.history-show`.
- **Rules:** Index can filter by doctor id query. Show requires `patient_id` match. `MedicareWorkflowTest` asserts history is private and a new visit does not replace the old one.

## Patient profile — `/patient/profile` and `/patient/profile/edit`

- **Show:** `dashboard.patient.show`. **Edit:** `dashboard.patient.edit`.
- **PUT** `patient.profile.update` uses `UpdatePatientProfileRequest`.
- **Writes:** user name, phone, gender, photo, and patient profile fields (age, blood type, health flags).
- **Test:** `ProfileCompletionTest::test_patient_saves_profile_details`.

## Doctor dashboard — `/doctor/dashboard`

- **Route:** `doctor.dashboard`. View `dashboard.doctor`.
- **Data:** today’s visits, counts, quick links. A doctor user without a `doctors` row can still open the dashboard (`AuthenticationTest`).
- **Links:** appointments, patients, referrals, availability, receptionists, profile edit. Practice URLs are not in the sidebar.

## Availability — `/doctor/availability`

- **GET** `doctor.availability` view `dashboard.doctor.availability`.
- **PUT** `doctor.availability.update` replaces weekly `time_slots` and duration/buffer. `UpdateDoctorAvailabilityRequest`. Days must be the request’s day list. End after start. Overlaps rejected in `withValidator`.
- **POST** `doctor.availability.exceptions.store` creates a leave or block in `doctor_availability_exceptions`.
- **DELETE** `availability.exceptions.destroy` only if the exception’s `doctor_id` matches. Otherwise 404.
- **Effect:** `AppointmentBooking` hides slots that overlap exceptions or have already started.

## Doctor appointments — `/doctor/appointments` and `/{appointment}`

- **List:** `doctor.appointments`. Same index view family as the patient list, scoped to this doctor. Tabs for upcoming, completed, cancelled are covered by `AppointmentPagesTest`.
- **Show:** 403 if the visit belongs to another doctor (`ConsultationReferralTest`).
- **Partial:** `dashboard/appointments/partials/doctor-visit.blade.php`.
- **Status form:** POST `doctor.appointments.update-status`. Doctor may send confirmed, checked_in, completed, cancelled, no_show. `applyStatus` still enforces the transition graph. Cancel requires `cancel_reason`.
- **Consult form:** PUT `doctor.appointments.consult.update`. Allowed only when status is confirmed, checked_in, or completed. Writes `consultations`. Does not start AI (`ConsultationReferralTest`).
- **Referral form:** POST `doctor.referrals.store` and preview POST. Visible when `canRefer()` is true.
- **Messages and reports:** same ideas as the patient page, authorized by doctor id. Report download uses scoped binding plus an explicit appointment id check.

## Analysis screen — `/doctor/appointments/{appointment}/analysis/{analysis?}`

- **View:** `dashboard.doctor.analysis.show`.
- **Start:** POST `appointments.analysis.store` with optional `report_ids` that must belong to the visit and be a supported file type.
- **Poll:** GET `analysis.status` returns JSON. `resources/js/analysis-poll.js`.
- **Review:** PUT `analysis.review`. **Approve:** POST `analysis.approve`.
- **Rule:** `AiAnalysisPolicy::manage` requires the doctor to own the visit.
- **Test failure:** the recipient of a referral was not supposed to see `Unapproved model summary.` on the referral page. The test failed because that string was present. Treat unapproved model text on the referral screen as a confirmed test failure, not as a theoretical risk.

## Patient chart — `/doctor/patients`, create, show

- **Index:** `dashboard.doctor.patients.index`. Patients who have a visit with this doctor or a `doctor_patients` link.
- **Create:** `dashboard.doctor.patients.create`. Modes “new” and “existing”. POST `doctor.patients.store`.
- **Search:** GET `doctor.patients.search` JSON for the lookup widget.
- **Show:** `dashboard.doctor.patients.show`. 404 if the user is not a patient or has no link and no visit with this doctor.
- **PUT** `doctor.patients.update` edits chart fields for a linked patient.
- **Analyses:** `doctor.patients.analyses` view `dashboard.doctor.patients.analyses`.
- **Test failure:** `test_doctor_can_add_a_new_patient_and_an_existing_one` failed on the create page. The title in the response was “Add a patient & Book Appointment”. One expected string was missing. The route is real.

## Receptionists — `/doctor/receptionists` and `/create`

- **Views:** `dashboard.doctor.receptionists.index`, `create`.
- **POST** creates a user with role `receptionist` and a `receptionists` row pointing at this doctor.
- **DELETE** only if that user’s receptionist row belongs to this doctor.
- **Test:** `ReceptionistDeskTest::test_only_the_doctor_can_add_and_remove_a_receptionist`.

## Referrals — list, preview, detail

- **List:** `/doctor/referrals` view `dashboard.doctor.referrals.index`. Incoming and outgoing.
- **Preview:** `/doctor/referrals/preview/{preview}` view `preview`. The id is a sample or “sent-” token, not a database id. `storePreview` validates against `MockReferralDirectory`, not the `doctors` table. This is a demonstration path beside the real `referrals.store` path.
- **Detail:** `/doctor/referrals/{referral}` view `show`. `involved()` allows the sender or the recipient. Others get 403.
- **Accept / decline:** only the recipient, only while status is `sent`.
- **Schedule:** recipient, only when status is `accepted`. Creates an appointment through `AppointmentBooking`.
- **Report download:** the report must be attached to that referral.

## Practice pages without sidebar links

| URL | Route | View | What it lists |
|---|---|---|---|
| `/doctor/consultations` | `doctor.consultations` | `dashboard.doctor.practice.consultations` | This doctor’s consultation notes |
| `/doctor/services` | `doctor.services` | `practice.services` | Fee and profile shortcut |
| `/doctor/reports` | `doctor.reports` | `practice.reports` | Reports on this doctor’s visits |
| `/doctor/reviews` | `doctor.reviews` | `practice.reviews` | `doctor_reviews` for this doctor |

They return 200 for the owning doctor (`DashboardWorkspaceTest` assertions for these four URLs were in the passing portion of that class; the class had two other failing tests). There is no menu item in `config/dashboard.php`. Treat them as implemented pages with no navigation entry.

## Doctor profile — `/doctor/profile` and `/edit`

- **Views:** `dashboard.doctor.profile.show`, `edit`.
- **PUT** `doctor.profile.update`. Phone must be 10 digits or empty. A shorter phone fails validation. `ProfileCompletionTest` posted `5551234` and failed with “The phone field format is invalid”, then the browser was sent to `/` rather than the profile show route. That failure is both a strict phone rule and a redirect that did not match the test’s expected `doctor.profile.show`.
- **Rule:** `approval_status` is not in the validated field list, so a doctor cannot approve themselves by adding the field. The failing test posts `approval_status=approved` and expects it to be ignored. The test did not reach that assertion because phone validation failed first.

## Receptionist dashboard and desk

| Page | URL | View |
|---|---|---|
| Dashboard | `/receptionist/dashboard` | `dashboard.receptionist` |
| Appointments | `/receptionist/appointments` | `dashboard.receptionist.appointments` |
| Book | `/receptionist/appointments/create` | `dashboard.receptionist.appointments.create` |
| Detail | `/receptionist/appointments/{appointment}` | `dashboard.receptionist.appointments.show` |
| Patients | `/receptionist/patients` | `dashboard.receptionist.patients` |
| Profile | `/receptionist/profile` | `dashboard.receptionist.show` |
| Edit profile | `/receptionist/profile/edit` | `dashboard.receptionist.edit` |

**Rules:** Every appointment action checks `appointment.doctor_id` against the receptionist’s assigned doctor. Store also rejects a posted `doctor_id` that does not match (403). Status changes cannot set `completed`. Search JSON is `receptionist.patients.search`, used by the header lookup in `layouts/dashboard.blade.php`.

**Script:** `dashboard/partials/desk-booking-script.blade.php` drives the slot picker on the create form.

## Admin

| Page | URL | View | POST/PUT |
|---|---|---|---|
| Dashboard | `/admin/dashboard` | `dashboard.admin.index` | none |
| Doctors | `/admin/doctors` | `dashboard.admin.doctors` | approve, reject |
| Users | `/admin/users` | `dashboard.admin.users` | PATCH active flag |
| Settings | `/admin/settings` | `dashboard.admin.settings` | PUT clinic settings |

Reject sets `approval_status` to `cancelled` and stores `rejection_reason`. Approve notifies via `ClinicNotifier`, which writes a database notification. It does not send email while mail is set to `log` unless the notifier also uses mail. `ClinicNotifier` uses `ClinicNotification`, whose `via` method returns the `database` channel only (see the class). No email is sent for approval.

## Account security — `/account/security`

- Any logged-in role. View `account.security`.
- PUT `/account/password` requires `current_password`, then `Password::defaults()` and `confirmed`.
- Also logs out other sessions (`logoutOtherDevices`).
- `MedicareWorkflowTest` covers the current-password requirement.

## Notifications — `/account/notifications`

- View `account.notifications`.
- Each item links to `account.notifications.read`, which marks it read.
- “Mark all” posts to `account.notifications.read-all`.
- Rows come from the `notifications` table. If the queue worker is not running, notifications created with `notify()` synchronously on the database channel still appear. Queued mail is not involved.

## Error, empty, and unauthorized screens

No `resources/views/errors/` directory was part of the Blade inventory used for this audit. Laravel’s default error pages apply.

| Situation | What the user sees |
|---|---|
| Guest on a private URL | Redirect to `/login` |
| Wrong role | 403 with message “You are not authorized to access this page.” |
| Unknown id | 404 |
| Empty lists | Copy inside each index Blade file, covered by `AppointmentPagesTest` for appointment lists |
| CSRF mismatch | 419 |
| Validation | The same form, with `$errors` |

## Endpoints that are not pages

| Route | Response |
|---|---|
| `doctors.reviews.index` | JSON reviews |
| `doctor.patients.search`, `receptionist.patients.search` | JSON patient matches |
| `*.messages.index` | JSON messages |
| `appointments.analysis.status` | JSON job state |
| `referrals.preview.store` | JSON preview payload |
| Report GET routes | File stream |
| `stripe.webhook` | Plain text |
| `GET /api/user` | JSON user |
| `GET /up` | Health |

These have no Blade body. Their authorization is the same middleware as the surrounding group, plus the controller checks named in chapter 05.

## Beginner path for one screen

Open My Appointments. The browser requests `GET /patient/bookings`. Middleware checks the session and `users.role`. `BookingController@index` loads appointments where `patient_id` is you. The view loops those rows. Clicking a row is another GET. Nothing in the list is saved until you submit a form on the detail page.
