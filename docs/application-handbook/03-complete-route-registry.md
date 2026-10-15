# 03 — Complete route registry

**Command:** `php artisan route:list --json` on 14 October 2026.

**Result:** 126 registered routes. The machine-readable copy is `docs/application-handbook/route-list.json`. The table at the bottom of this file is that list. Middleware shown by Artisan is the route middleware plus the group name `web` or `api`. The classes inside `web` are listed in [chapter 02](02-request-lifecycle.md). They are not repeated on every row.

`HEAD` is registered beside `GET` by Laravel. A browser page load uses `GET`.

## How to find a route

1. Copy the path from the browser, without the host.
2. Run `php artisan route:list --path=patient/bookings`.
3. Open the file in the `action` column.
4. For a named route used in Blade, search the codebase for `route('that.name')`.

## Concepts that this application actually uses

### Route names

`->name('patient.bookings.show')` creates a name. Blade and redirects call `route('patient.bookings.show', $appointment)`. The name is not the URL. Several auth POSTs have **no name** (`POST /login`, `POST /register`, `POST /confirm-password`). Forms post to `route('login')` only for the GET name, or they hard-code the action. Login’s form uses `route('login')`, which is the GET name; the form method is POST, so the browser posts to the same path `/login`. That works because the path is shared. It is a Breeze pattern.

### Groups, prefixes, middleware

`routes/web.php` groups:

| Group | Prefix | Name prefix | Middleware |
|---|---|---|---|
| Patient | `/patient` | `patient.` | `auth`, `role:patient` |
| Doctor | `/doctor` | `doctor.` | `auth`, `role:doctor` |
| Receptionist | `/receptionist` | `receptionist.` | `auth`, `role:receptionist` |
| Admin | `/admin` | `admin.` | `auth`, `role:admin` |
| Account security and notifications | `/account` | `account.` | `auth` |
| Breeze profile | `/profile` | `profile.` | `auth` |
| Guest auth | none | mixed | `guest` |

`role:patient` is the alias from `bootstrap/app.php`. The parameter `patient` is passed to `EnsureUserHasRole`.

### Resource routes

This application does **not** use `Route::resource`. Every route is written out. That is why create, show, and search can be ordered explicitly.

### Model binding

| Parameter | Binds as | Key |
|---|---|---|
| `{appointment}` | `Appointment` when type-hinted | `id` |
| `{doctor}` on book/review routes | `Doctor` | `id` |
| `{slug}` on `doctors.show` | Plain string. Not model binding | User slug via a query |
| `{patient}` | `User` | `id`. The controller then checks `role === 'patient'` |
| `{receptionist}` | `User` | `id`. The controller checks the receptionist profile’s doctor |
| `{referral}` | `Referral` | `id` |
| `{analysis}` | `AiAnalysis` | `id`. Optional on the show route (`{analysis?}`) |
| `{exception}` | `DoctorAvailabilityException` | `id` |
| `{report}` | `AppointmentReport` | `id` |
| `{notification}` | Notification id string | Database notification id |
| `{preview}` | String matching the `where` regex | Not a model |
| `{token}` | Password reset token string | Not a model |
| `{user}` on admin | `User` | `id` |

Scoped bindings (`->scopeBindings()` in `routes/web.php`) apply to:

- `patient.bookings.reports.destroy`
- `patient.bookings.reports.show`
- `doctor.appointments.reports.show`

Artisan does not print that flag in the middleware column. It is still active.

### Throttle

| Route | Limit |
|---|---|
| `doctors.reviews.store` | 20 per minute |
| `doctor.appointments.analysis.store` | 10 per minute |
| analysis review and approve | 20 per minute |
| `verification.send` and `verification.verify` | 6 per minute |
| Login | 5 attempts per email+IP inside `LoginRequest`, not route middleware |

### Framework routes that are not clinic features

| URI | Why it exists |
|---|---|
| `GET /up` | Health check from `->withRouting(health: '/up')` |
| `GET /api/user` | Sanctum sample. Returns the user as JSON. No clinic logic |
| `GET /sanctum/csrf-cookie` | Sanctum cookie endpoint |
| `GET/PUT /storage/{path}` | Because the `local` disk has `'serve' => true`. Private files need a valid signature. See chapter 01 |

## Route cards by feature

The cards below are the behavior. The table is the inventory. Each card uses the teaching shape from the audit brief in a compact form. Shared failure behavior is stated once:

- No session on an `auth` route: redirect to `route('login')`.
- Wrong role: HTTP 403 from `EnsureUserHasRole`.
- Validation failure on an HTML form: redirect back, session errors, old input.
- Unknown id: HTTP 404 from model binding or `firstOrFail`.

### Public pages

| Name | Action | View | Notes |
|---|---|---|---|
| `home` | Closure in `routes/web.php` | `public.home` | `PublicCatalog::featuredDoctors()` and `services()` |
| `about`, `contact`, `faq`, `security` | `Route::view` | `public.about`, `public.contact`, `public.faq`, `public.security` | No database |
| `services.index` | Closure | `public.services.index` | `PublicCatalog::paginatedServices()` |

`PublicCatalog` is application code in `app/Support/PublicCatalog.php`, not a CMS.

### Doctors and reviews

**`doctors.index`** — `Public\DoctorController@index`. Approved doctors whose user is active. Optional `query` filters specialization or user name with `LIKE`. Paginate 9. View `public.doctors.index`. No auth.

**`doctors.show`** — `show(string $slug)`. Doctor is found by the related user’s slug, including doctors who are not approved. View `public.doctors.show` plus `DoctorReviews::page`. Booking links elsewhere still require approval.

**`doctors.book`** — public form `public.doctors.book`. `BookingController::create`. Does not create a row.

**`doctors.book.remember`** — POST, guest allowed. Stores a booking draft in the session and redirects toward login or the form. `BookingController::remember`.

**`doctors.book.store`** — auth + patient. `StorePatientAppointmentRequest`. `AppointmentBooking` creates the visit. Paid path may redirect to Stripe. Free path confirms. Success view is `checkout.success` via `checkout.success`.

**`doctors.reviews.index`** — JSON page of reviews.

**`doctors.reviews.begin`** — redirect that starts the review dialog flow for a logged-in patient.

**`doctors.reviews.store`** — `StoreDoctorReviewRequest`. Patient role checked again in the controller. One review per user per doctor (unique `doctor_id, user_id`). Updates the existing row if the patient reviews again. Throttle 20/minute.

### Patient

All require `auth` and `role:patient`. Controllers often repeat `abort_unless(role === patient)` and `abort_unless(patient_id matches)`.

| Name | Method | Writes? | View or response |
|---|---|---|---|
| `patient.dashboard` | index | no | `dashboard.patient` |
| `patient.book` | index | no | `dashboard.patient.doctors` |
| `patient.book.create` | create | no | `dashboard.patient.book` |
| `patient.bookings` | index | no | `dashboard.appointments.index` |
| `patient.bookings.show` | show | no | `dashboard.appointments.show` |
| `patient.bookings.reports.store` | storeReports | yes, files + `appointment_reports` | redirect |
| `patient.bookings.reports.destroy` | destroyReport | deletes file and row | redirect. Scoped binding |
| `patient.bookings.reports.show` | report | no | streamed file. Scoped binding. Owner check |
| `patient.bookings.messages.index` | messages | no | JSON |
| `patient.bookings.messages.store` | storeMessage | `appointment_messages` | redirect or JSON |
| `patient.bookings.cancel` | cancel | status `cancelled` | redirect. `canPatientCancel()` |
| `patient.bookings.reschedule` | reschedule | date/time + `appointment_reschedules` | redirect |
| `patient.history` | index | no | `dashboard.patient.history` |
| `patient.history.show` | show | no | `dashboard.patient.history-show`. Owner check |
| `patient.profile.show` / `edit` / `update` | Account `ProfileController` | update writes user + `patient_profiles` | `dashboard.patient.show` and `dashboard.patient.edit` |

`GET /patient/book?doctor={id}` redirects to `patient.book.create`. That is controller behavior covered by `DashboardWorkspaceTest`.

### Doctor

All require `auth` and `role:doctor`. Visit actions check `appointment.doctor_id`.

| Area | Names | Notes |
|---|---|---|
| Home | `doctor.dashboard` | `dashboard.doctor` |
| Schedule | `doctor.availability`, `availability.update`, `availability.exceptions.store`, `availability.exceptions.destroy` | Weekly slots and leave windows |
| Visits | `doctor.appointments`, `appointments.show`, `appointments.update-status`, `appointments.consult.update`, messages, `appointments.reports.show` | Status uses `UpdateAppointmentStatusRequest` then `Appointment::applyStatus`. Consult uses `UpdateConsultationRequest` |
| Analysis | `appointments.analysis`, `analysis.store`, `analysis.status`, `analysis.review`, `analysis.approve` | Policy `AiAnalysisPolicy`. Store is throttled and uses `dispatchSync` |
| Patients | `doctor.patients`, `create`, `store`, `search`, `show`, `update`, `patients.analyses` | Search is JSON. Store creates a patient or links an existing one and books |
| Staff | `doctor.receptionists` index, create, store, destroy | Creates a user with role `receptionist` |
| Referrals | index, preview, show, store, preview.store, accept, decline, schedule, reports.show | Preview ids are mock/sample ids, not `referrals.id` |
| Practice pages | `doctor.consultations`, `services`, `reports`, `reviews` | Reachable by URL and tests. Not linked in `config/dashboard.php` sidebar |
| Profile | `doctor.profile.show`, `edit`, `update` | `UpdateDoctorProfileRequest`. Cannot set `approval_status` through the validated fields |

### Receptionist

Requires `auth` and `role:receptionist`. Appointment queries are limited to `receptionist.doctor_id`.

| Name | Behavior |
|---|---|
| `receptionist.dashboard` | `dashboard.receptionist` |
| `receptionist.appointments` | List. Query `range` is `today` or `upcoming` from the sidebar |
| `receptionist.appointments.create` / `store` | Desk booking. `StoreReceptionistAppointmentRequest` requires an assigned doctor |
| `receptionist.appointments.show` | Detail |
| `receptionist.appointments.update-status` | Same status request as doctors, but the allowed list excludes `completed` |
| `receptionist.appointments.reschedule` | Moves the visit if the slot is open |
| `receptionist.patients` / `patients.search` | Roster and JSON search |
| `receptionist.profile` / `edit` / `update` | Account profile for the receptionist |

### Admin

| Name | Behavior |
|---|---|
| `admin.dashboard` | Counts and recent users. View `dashboard.admin.index` |
| `admin.doctors` | Paginated doctors |
| `admin.doctors.approve` | Sets `approval_status` to `approved`, clears rejection reason, notifies |
| `admin.doctors.reject` | Requires `rejection_reason`. Sets status to `cancelled` |
| `admin.users` | Filter `q`, `role`, `status` |
| `admin.users.update` | PATCH `is_active` 0 or 1. Cannot deactivate yourself or the last active admin |
| `admin.settings` / `settings.update` | `clinic_settings` row: horizon, notice, cancellation hours, payment policy, timezone |

### Account and Breeze profile

| Name | Who | Behavior |
|---|---|---|
| `account.security` | any logged-in role | `account.security` view |
| `account.password.update` | any logged-in role | Current password, new password, `logoutOtherDevices` |
| `account.notifications` | any logged-in role | Database notifications |
| `account.notifications.read` | any | Marks one read and redirects to its URL if present |
| `account.notifications.read-all` | any | Marks all read |
| `profile.edit` / `update` / `destroy` | any logged-in user | Breeze screens on `layouts.app`. Destroy deletes the user after password confirmation. Cascade deletes related rows. The dashboard menus do not point here; `layouts/navigation.blade.php` does |

`PUT /password` (`password.update`) is a second password form from Breeze (`Auth\PasswordController`). The dashboard uses `account.password.update` instead.

### Auth

Guest routes redirect authenticated users away. Passwords are hashed. Registration creates the profile inside a transaction.

| Name | View / result |
|---|---|
| `register` GET | `auth.register` extending `layouts.public` |
| POST `/register` | User + patient profile or pending doctor. Login. Redirect `dashboard` |
| `login` GET | `auth.login` |
| POST `/login` | `LoginRequest::authenticate`. Restores a booking draft when one is in the session |
| `logout` | Invalidates session |
| `password.request` / `password.email` | Forgot password. Mail is logged, not delivered, with the log mailer |
| `password.reset` / `password.store` | Reset form and submit |
| `verification.notice` / `verify` / `send` | Email verification. Not required by the User model |
| `password.confirm` | Confirm-password screen |

### Stripe

`POST /stripe/webhook` is public and CSRF-exempt. `StripeWebhookController` verifies `Stripe-Signature`. `checkout.session.completed` marks the appointment paid and `confirmed` when the amount and currency match. Expired or failed checkout cancels an unpaid appointment. Response is plain text `ok` or `Invalid payload`.

## Full machine inventory

| Method | URI | Name | Action | Middleware recorded by Artisan |
|---|---|---|---|---|
| `GET|HEAD` | `/` | `home` | `Closure` | `web` |
| `GET|HEAD` | `/about` | `about` | `Illuminate\Routing\ViewController` | `web` |
| `GET|HEAD` | `/account/notifications` | `account.notifications` | `App\Http\Controllers\Account\NotificationController@index` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `POST` | `/account/notifications/read` | `account.notifications.read-all` | `App\Http\Controllers\Account\NotificationController@readAll` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `GET|HEAD` | `/account/notifications/{notification}` | `account.notifications.read` | `App\Http\Controllers\Account\NotificationController@read` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `PUT` | `/account/password` | `account.password.update` | `App\Http\Controllers\Account\SecurityController@update` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `GET|HEAD` | `/account/security` | `account.security` | `App\Http\Controllers\Account\SecurityController@edit` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `GET|HEAD` | `/admin/dashboard` | `admin.dashboard` | `App\Http\Controllers\Admin\DashboardController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:admin` |
| `GET|HEAD` | `/admin/doctors` | `admin.doctors` | `App\Http\Controllers\Admin\DashboardController@doctors` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:admin` |
| `POST` | `/admin/doctors/{doctor}/approve` | `admin.doctors.approve` | `App\Http\Controllers\Admin\DashboardController@approve` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:admin` |
| `POST` | `/admin/doctors/{doctor}/reject` | `admin.doctors.reject` | `App\Http\Controllers\Admin\DashboardController@reject` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:admin` |
| `GET|HEAD` | `/admin/settings` | `admin.settings` | `App\Http\Controllers\Admin\DashboardController@settings` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:admin` |
| `PUT` | `/admin/settings` | `admin.settings.update` | `App\Http\Controllers\Admin\DashboardController@updateSettings` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:admin` |
| `GET|HEAD` | `/admin/users` | `admin.users` | `App\Http\Controllers\Admin\DashboardController@users` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:admin` |
| `PATCH` | `/admin/users/{user}` | `admin.users.update` | `App\Http\Controllers\Admin\DashboardController@updateUser` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:admin` |
| `GET|HEAD` | `/api/user` | `—` | `Closure` | `api, Illuminate\Auth\Middleware\Authenticate:sanctum` |
| `GET|HEAD` | `/checkout/appointments/{appointment}/success` | `checkout.success` | `App\Http\Controllers\Public\BookingController@success` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/confirm-password` | `password.confirm` | `App\Http\Controllers\Auth\ConfirmablePasswordController@show` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `POST` | `/confirm-password` | `—` | `App\Http\Controllers\Auth\ConfirmablePasswordController@store` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `GET|HEAD` | `/contact` | `contact` | `Illuminate\Routing\ViewController` | `web` |
| `GET|HEAD` | `/dashboard` | `dashboard` | `Closure` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `GET|HEAD` | `/doctor/appointments` | `doctor.appointments` | `App\Http\Controllers\Doctor\AppointmentController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/appointments/{appointment}` | `doctor.appointments.show` | `App\Http\Controllers\Doctor\AppointmentController@show` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/appointments/{appointment}/analysis` | `doctor.appointments.analysis.store` | `App\Http\Controllers\Doctor\AiAnalysisController@store` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor, Illuminate\Routing\Middleware\ThrottleRequests:10,1` |
| `GET|HEAD` | `/doctor/appointments/{appointment}/analysis/{analysis?}` | `doctor.appointments.analysis` | `App\Http\Controllers\Doctor\AiAnalysisController@show` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/appointments/{appointment}/analysis/{analysis}/approve` | `doctor.appointments.analysis.approve` | `App\Http\Controllers\Doctor\AiAnalysisController@approve` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor, Illuminate\Routing\Middleware\ThrottleRequests:20,1` |
| `PUT` | `/doctor/appointments/{appointment}/analysis/{analysis}/review` | `doctor.appointments.analysis.review` | `App\Http\Controllers\Doctor\AiAnalysisController@update` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor, Illuminate\Routing\Middleware\ThrottleRequests:20,1` |
| `GET|HEAD` | `/doctor/appointments/{appointment}/analysis/{analysis}/status` | `doctor.appointments.analysis.status` | `App\Http\Controllers\Doctor\AiAnalysisController@status` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `PUT` | `/doctor/appointments/{appointment}/consult` | `doctor.appointments.consult.update` | `App\Http\Controllers\Doctor\AppointmentController@updateConsult` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/appointments/{appointment}/messages` | `doctor.appointments.messages.index` | `App\Http\Controllers\Doctor\AppointmentController@messages` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/appointments/{appointment}/messages` | `doctor.appointments.messages.store` | `App\Http\Controllers\Doctor\AppointmentController@storeMessage` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/appointments/{appointment}/referrals` | `doctor.referrals.store` | `App\Http\Controllers\Doctor\ReferralController@store` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/appointments/{appointment}/referrals/preview` | `doctor.referrals.preview.store` | `App\Http\Controllers\Doctor\ReferralController@storePreview` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/appointments/{appointment}/reports/{report}` | `doctor.appointments.reports.show` | `App\Http\Controllers\Doctor\AppointmentController@report` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/appointments/{appointment}/status` | `doctor.appointments.update-status` | `App\Http\Controllers\Doctor\AppointmentController@updateStatus` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/availability` | `doctor.availability` | `App\Http\Controllers\Doctor\AvailabilityController@edit` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `PUT` | `/doctor/availability` | `doctor.availability.update` | `App\Http\Controllers\Doctor\AvailabilityController@update` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/availability/exceptions` | `doctor.availability.exceptions.store` | `App\Http\Controllers\Doctor\AvailabilityController@storeException` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `DELETE` | `/doctor/availability/exceptions/{exception}` | `doctor.availability.exceptions.destroy` | `App\Http\Controllers\Doctor\AvailabilityController@destroyException` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/consultations` | `doctor.consultations` | `App\Http\Controllers\Doctor\PracticeController@consultations` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/dashboard` | `doctor.dashboard` | `App\Http\Controllers\Doctor\DashboardController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/patients` | `doctor.patients` | `App\Http\Controllers\Doctor\PatientController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/patients` | `doctor.patients.store` | `App\Http\Controllers\Doctor\PatientController@store` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/patients/create` | `doctor.patients.create` | `App\Http\Controllers\Doctor\PatientController@create` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/patients/search` | `doctor.patients.search` | `App\Http\Controllers\Doctor\PatientController@search` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/patients/{patient}` | `doctor.patients.show` | `App\Http\Controllers\Doctor\PatientController@show` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `PUT` | `/doctor/patients/{patient}` | `doctor.patients.update` | `App\Http\Controllers\Doctor\PatientController@update` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/patients/{patient}/analyses` | `doctor.patients.analyses` | `App\Http\Controllers\Doctor\AiAnalysisController@forPatient` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/profile` | `doctor.profile.show` | `App\Http\Controllers\Account\ProfileController@show` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `PUT` | `/doctor/profile` | `doctor.profile.update` | `App\Http\Controllers\Account\ProfileController@updateDoctor` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/profile/edit` | `doctor.profile.edit` | `App\Http\Controllers\Account\ProfileController@edit` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/receptionists` | `doctor.receptionists` | `App\Http\Controllers\Doctor\ReceptionistController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/receptionists` | `doctor.receptionists.store` | `App\Http\Controllers\Doctor\ReceptionistController@store` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/receptionists/create` | `doctor.receptionists.create` | `App\Http\Controllers\Doctor\ReceptionistController@create` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `DELETE` | `/doctor/receptionists/{receptionist}` | `doctor.receptionists.destroy` | `App\Http\Controllers\Doctor\ReceptionistController@destroy` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/referrals` | `doctor.referrals` | `App\Http\Controllers\Doctor\ReferralController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/referrals/preview/{preview}` | `doctor.referrals.preview` | `App\Http\Controllers\Doctor\ReferralController@preview` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/referrals/{referral}` | `doctor.referrals.show` | `App\Http\Controllers\Doctor\ReferralController@show` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/referrals/{referral}/accept` | `doctor.referrals.accept` | `App\Http\Controllers\Doctor\ReferralController@accept` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/referrals/{referral}/decline` | `doctor.referrals.decline` | `App\Http\Controllers\Doctor\ReferralController@decline` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/referrals/{referral}/reports/{report}` | `doctor.referrals.reports.show` | `App\Http\Controllers\Doctor\ReferralController@report` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `POST` | `/doctor/referrals/{referral}/schedule` | `doctor.referrals.schedule` | `App\Http\Controllers\Doctor\ReferralController@schedule` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/reports` | `doctor.reports` | `App\Http\Controllers\Doctor\PracticeController@reports` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/reviews` | `doctor.reviews` | `App\Http\Controllers\Doctor\PracticeController@reviews` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctor/services` | `doctor.services` | `App\Http\Controllers\Doctor\PracticeController@services` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:doctor` |
| `GET|HEAD` | `/doctors` | `doctors.index` | `App\Http\Controllers\Public\DoctorController@index` | `web` |
| `GET|HEAD` | `/doctors/{doctor}/book` | `doctors.book` | `App\Http\Controllers\Public\BookingController@create` | `web` |
| `POST` | `/doctors/{doctor}/book` | `doctors.book.store` | `App\Http\Controllers\Public\BookingController@store` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `POST` | `/doctors/{doctor}/book/continue` | `doctors.book.remember` | `App\Http\Controllers\Public\BookingController@remember` | `web` |
| `GET|HEAD` | `/doctors/{doctor}/reviews` | `doctors.reviews.index` | `App\Http\Controllers\Public\ReviewController@index` | `web` |
| `POST` | `/doctors/{doctor}/reviews` | `doctors.reviews.store` | `App\Http\Controllers\Public\ReviewController@store` | `web, Illuminate\Routing\Middleware\ThrottleRequests:20,1` |
| `GET|HEAD` | `/doctors/{doctor}/reviews/begin` | `doctors.reviews.begin` | `App\Http\Controllers\Public\ReviewController@begin` | `web` |
| `GET|HEAD` | `/doctors/{slug}` | `doctors.show` | `App\Http\Controllers\Public\DoctorController@show` | `web` |
| `POST` | `/email/verification-notification` | `verification.send` | `App\Http\Controllers\Auth\EmailVerificationNotificationController@store` | `web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ThrottleRequests:6,1` |
| `GET|HEAD` | `/faq` | `faq` | `Illuminate\Routing\ViewController` | `web` |
| `GET|HEAD` | `/forgot-password` | `password.request` | `App\Http\Controllers\Auth\PasswordResetLinkController@create` | `web, Illuminate\Auth\Middleware\RedirectIfAuthenticated` |
| `POST` | `/forgot-password` | `password.email` | `App\Http\Controllers\Auth\PasswordResetLinkController@store` | `web, Illuminate\Auth\Middleware\RedirectIfAuthenticated` |
| `GET|HEAD` | `/login` | `login` | `App\Http\Controllers\Auth\AuthenticatedSessionController@create` | `web, Illuminate\Auth\Middleware\RedirectIfAuthenticated` |
| `POST` | `/login` | `—` | `App\Http\Controllers\Auth\AuthenticatedSessionController@store` | `web, Illuminate\Auth\Middleware\RedirectIfAuthenticated` |
| `POST` | `/logout` | `logout` | `App\Http\Controllers\Auth\AuthenticatedSessionController@destroy` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `PUT` | `/password` | `password.update` | `App\Http\Controllers\Auth\PasswordController@update` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `GET|HEAD` | `/patient/book` | `patient.book` | `App\Http\Controllers\Patient\BookDoctorController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/book/{doctor}` | `patient.book.create` | `App\Http\Controllers\Patient\BookDoctorController@create` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/bookings` | `patient.bookings` | `App\Http\Controllers\Patient\BookingController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/bookings/{appointment}` | `patient.bookings.show` | `App\Http\Controllers\Patient\BookingController@show` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `POST` | `/patient/bookings/{appointment}/cancel` | `patient.bookings.cancel` | `App\Http\Controllers\Patient\BookingController@cancel` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/bookings/{appointment}/messages` | `patient.bookings.messages.index` | `App\Http\Controllers\Patient\BookingController@messages` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `POST` | `/patient/bookings/{appointment}/messages` | `patient.bookings.messages.store` | `App\Http\Controllers\Patient\BookingController@storeMessage` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `POST` | `/patient/bookings/{appointment}/reports` | `patient.bookings.reports.store` | `App\Http\Controllers\Patient\BookingController@storeReports` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `DELETE` | `/patient/bookings/{appointment}/reports/{report}` | `patient.bookings.reports.destroy` | `App\Http\Controllers\Patient\BookingController@destroyReport` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/bookings/{appointment}/reports/{report}` | `patient.bookings.reports.show` | `App\Http\Controllers\Patient\BookingController@report` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `POST` | `/patient/bookings/{appointment}/reschedule` | `patient.bookings.reschedule` | `App\Http\Controllers\Patient\BookingController@reschedule` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/dashboard` | `patient.dashboard` | `App\Http\Controllers\Patient\DashboardController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/history` | `patient.history` | `App\Http\Controllers\Patient\HistoryController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/history/{appointment}` | `patient.history.show` | `App\Http\Controllers\Patient\HistoryController@show` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/profile` | `patient.profile.show` | `App\Http\Controllers\Account\ProfileController@show` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `PUT` | `/patient/profile` | `patient.profile.update` | `App\Http\Controllers\Account\ProfileController@updatePatient` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/patient/profile/edit` | `patient.profile.edit` | `App\Http\Controllers\Account\ProfileController@edit` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:patient` |
| `GET|HEAD` | `/profile` | `profile.edit` | `App\Http\Controllers\ProfileController@edit` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `PATCH` | `/profile` | `profile.update` | `App\Http\Controllers\ProfileController@update` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `DELETE` | `/profile` | `profile.destroy` | `App\Http\Controllers\ProfileController@destroy` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `GET|HEAD` | `/receptionist/appointments` | `receptionist.appointments` | `App\Http\Controllers\Receptionist\AppointmentController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `POST` | `/receptionist/appointments` | `receptionist.appointments.store` | `App\Http\Controllers\Receptionist\AppointmentController@store` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `GET|HEAD` | `/receptionist/appointments/create` | `receptionist.appointments.create` | `App\Http\Controllers\Receptionist\AppointmentController@create` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `GET|HEAD` | `/receptionist/appointments/{appointment}` | `receptionist.appointments.show` | `App\Http\Controllers\Receptionist\AppointmentController@show` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `POST` | `/receptionist/appointments/{appointment}/reschedule` | `receptionist.appointments.reschedule` | `App\Http\Controllers\Receptionist\AppointmentController@reschedule` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `POST` | `/receptionist/appointments/{appointment}/status` | `receptionist.appointments.update-status` | `App\Http\Controllers\Receptionist\AppointmentController@updateStatus` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `GET|HEAD` | `/receptionist/dashboard` | `receptionist.dashboard` | `App\Http\Controllers\Receptionist\DashboardController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `GET|HEAD` | `/receptionist/patients` | `receptionist.patients` | `App\Http\Controllers\Receptionist\PatientController@index` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `GET|HEAD` | `/receptionist/patients/search` | `receptionist.patients.search` | `App\Http\Controllers\Receptionist\PatientController@search` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `GET|HEAD` | `/receptionist/profile` | `receptionist.profile` | `App\Http\Controllers\Account\ProfileController@showReceptionist` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `PUT` | `/receptionist/profile` | `receptionist.profile.update` | `App\Http\Controllers\Account\ProfileController@updateReceptionist` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `GET|HEAD` | `/receptionist/profile/edit` | `receptionist.profile.edit` | `App\Http\Controllers\Account\ProfileController@editReceptionist` | `web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\EnsureUserHasRole:receptionist` |
| `GET|HEAD` | `/register` | `register` | `App\Http\Controllers\Auth\RegisteredUserController@create` | `web, Illuminate\Auth\Middleware\RedirectIfAuthenticated` |
| `POST` | `/register` | `—` | `App\Http\Controllers\Auth\RegisteredUserController@store` | `web, Illuminate\Auth\Middleware\RedirectIfAuthenticated` |
| `POST` | `/reset-password` | `password.store` | `App\Http\Controllers\Auth\NewPasswordController@store` | `web, Illuminate\Auth\Middleware\RedirectIfAuthenticated` |
| `GET|HEAD` | `/reset-password/{token}` | `password.reset` | `App\Http\Controllers\Auth\NewPasswordController@create` | `web, Illuminate\Auth\Middleware\RedirectIfAuthenticated` |
| `GET|HEAD` | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` | `web` |
| `GET|HEAD` | `/security` | `security` | `Illuminate\Routing\ViewController` | `web` |
| `GET|HEAD` | `/services` | `services.index` | `Closure` | `web` |
| `GET|HEAD` | `/storage/{path}` | `storage.local` | `Closure` | `` |
| `PUT` | `/storage/{path}` | `storage.local.upload` | `Closure` | `` |
| `POST` | `/stripe/webhook` | `stripe.webhook` | `App\Http\Controllers\Public\StripeWebhookController` | `web` |
| `GET|HEAD` | `/up` | `—` | `Closure` | `` |
| `GET|HEAD` | `/verify-email` | `verification.notice` | `App\Http\Controllers\Auth\EmailVerificationPromptController` | `web, Illuminate\Auth\Middleware\Authenticate` |
| `GET|HEAD` | `/verify-email/{id}/{hash}` | `verification.verify` | `App\Http\Controllers\Auth\VerifyEmailController` | `web, Illuminate\Auth\Middleware\Authenticate, Illuminate\Routing\Middleware\ValidateSignature, Illuminate\Routing\Middleware\ThrottleRequests:6,1` |



Source: `php artisan route:list --json`. Scoped bindings and the referral preview pattern are not columns in that output; they are documented above from `routes/web.php`.

