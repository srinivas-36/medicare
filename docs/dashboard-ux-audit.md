# Medicare dashboard UX audit

Audit date: 13 October 2026. Source of truth is the current Laravel application, not the earlier implementation plan.

## Stack verified in the repository

- Laravel 12, Blade, Tailwind (tokens in `resources/css/app.css`), Vite, Alpine.js.
- Session authentication. Roles on `users.role`: `patient`, `doctor`, `receptionist`, `admin`.
- Role routes use the `role` middleware in `routes/web.php`.
- Tests use SQLite in memory. The app timezone in `config/app.php` is `UTC`. Clinic clock, horizon, notice, and cancellation live on `clinic_settings`.

## Scheduling model

Doctors do not store one row per appointment time. They store weekly working windows on `time_slots` (`day`, `starting_time`, `ending_time`, `is_available`) plus `doctors.appointment_duration` and `doctors.appointment_buffer`. Date exceptions live on `doctor_availability_exceptions` (`unavailable`, `extra`, `override`).

`App\Services\AppointmentBooking` is the only slot engine. Public booking, the patient dashboard, the receptionist desk, doctor-created visits, and referral scheduling all call it. The server rechecks the slot inside a transaction that locks the doctor row. Active visits also write a unique `appointments.active_slot` key. Cancelled visits clear that key.

### Defect found

`slicesFor()` treated each saved window as a single bookable visit. A doctor who saved Wednesday 09:00–12:00 with a 30-minute visit length was offered to patients as one 09:00–12:00 appointment. `sliceEnd()` then stored that whole window as `ending_time`. A window that had already started was dropped entirely, so later times on the same day disappeared. Buffer minutes were not used to space new openings.

That behavior was locked in by feature tests (`"start":"09:00","end":"12:00"`).

### Correction

Each saved `time_slots` row is one bookable opening. The start and end shown to patients, receptionists, and referral booking are that row’s `starting_time` and `ending_time`. The engine does not invent extra times inside the period.

- A row that has already started, or that overlaps a booked visit, is hidden.
- Full-day leave removes the day. A timed break hides any saved row it overlaps. `override` replaces that day’s rows. `extra` adds the hours the doctor entered on the exception.
- Clocks stay in `clinic_settings.timezone`.
- A submitted start or end that is not one of those saved rows is rejected.
- Reschedule is a date field, then buttons for that date’s saved slots. The same picker is used for receptionist reschedule and referral booking.

Patients still never see a fabricated default day. If the doctor saved no hours for that weekday, the list is empty.

## Authenticated pages

Navigation is `config/dashboard.php` rendered by `components/dashboard/sidebar.blade.php` inside `layouts/dashboard.blade.php`. A link is shown only when its named route exists. The active item uses the route name, with query matching for the receptionist today and calendar links.

### Patient

| Page | Route | Controller | View | Purpose |
| --- | --- | --- | --- | --- |
| Overview | `patient.dashboard` | `Patient\DashboardController@index` | `dashboard/patient.blade.php` | Next visit, recent completed visits, updates |
| Find a doctor | `patient.book` | `Patient\BookDoctorController@index` | `dashboard/patient/doctors.blade.php` | Search approved doctors and book |
| Book redirect | `patient.book.create` | `BookDoctorController@create` | redirects to `patient.book` | Keeps an older URL |
| Appointments | `patient.bookings` | `Patient\BookingController@index` | `dashboard/appointments/index.blade.php` | Filter, search, paginate |
| Appointment | `patient.bookings.show` | `BookingController@show` | `dashboard/appointments/show.blade.php` | Status, reports, messages, reschedule, cancel |
| History | `patient.history` | `Patient\HistoryController` | `dashboard/patient/history.blade.php` | Completed care |
| History detail | `patient.history.show` | `HistoryController@show` | `dashboard/patient/history-show.blade.php` | One visit record |
| Profile | `patient.profile.show` / `.edit` | `Account\ProfileController` | `dashboard/patient/show.blade.php`, `edit.blade.php` | Contact and health notes |
| Notifications | `account.notifications` | `Account\NotificationController` | `account/notifications.blade.php` | In-app inbox |
| Security | `account.security` | `Account\SecurityController` | `account/security.blade.php` | Password change |

`dashboard/patient/book.blade.php` is not returned by a controller. The live booking screen is `doctors.blade.php` plus `partials/appointment-booking-form.blade.php` on the public book page.

### Doctor

| Page | Route | Controller | View | Purpose |
| --- | --- | --- | --- | --- |
| Today | `doctor.dashboard` | `Doctor\DashboardController@index` | `dashboard/doctor.blade.php` | Today’s list, next visit, referrals, follow-ups |
| Availability | `doctor.availability` | `Doctor\AvailabilityController` | `dashboard/doctor/availability.blade.php` | Weekly hours and exceptions |
| Appointments | `doctor.appointments` | `Doctor\AppointmentController` | `dashboard/appointments/index.blade.php` | Shared list |
| Visit | `doctor.appointments.show` | `AppointmentController@show` | `dashboard/appointments/show.blade.php` | Status, consult, referral, messages |
| Patients | `doctor.patients` | `Doctor\PatientController` | `dashboard/doctor/patients/index.blade.php` | Patients this doctor can open |
| Add patient | `doctor.patients.create` | `PatientController@create` | `dashboard/doctor/patients/create.blade.php` | Register or match a patient and book |
| Patient chart | `doctor.patients.show` | `PatientController@show` | `dashboard/doctor/patients/show.blade.php` | Visits with this doctor |
| Receptionists | `doctor.receptionists` | `Doctor\ReceptionistController` | `dashboard/doctor/receptionists/*` | Add and remove desk staff |
| Referrals | `doctor.referrals` | `Doctor\ReferralController` | `dashboard/doctor/referrals/*` | Inbox, accept, decline, schedule |
| Profile | `doctor.profile.show` / `.edit` | `Account\ProfileController` | `dashboard/doctor/profile/*` | Public profile, qualifications, hours |
| Notifications, security | shared account routes | | | |

### Receptionist

| Page | Route | Controller | View | Purpose |
| --- | --- | --- | --- | --- |
| Today’s queue | `receptionist.dashboard` | `Receptionist\DashboardController` | `dashboard/receptionist.blade.php` | Today’s board and check-in |
| Appointments | `receptionist.appointments` | `Receptionist\AppointmentController` | `dashboard/receptionist/appointments.blade.php` | Today, upcoming, or all |
| Book | `receptionist.appointments.create` | `AppointmentController@create` | `appointments/create.blade.php` | Search a patient and book the assigned doctor |
| Visit | `receptionist.appointments.show` | `AppointmentController@show` | `appointments/show.blade.php` | Status and reschedule |
| Patients | `receptionist.patients` | `Receptionist\PatientController` | `dashboard/receptionist/patients.blade.php` | Search and jump to booking |
| Profile | `receptionist.profile` | `Account\ProfileController` | `dashboard/receptionist/show.blade.php`, `edit.blade.php` | Desk profile |
| Unassigned | same dashboard | | `partials/unassigned.blade.php` | Shown when no doctor is linked |

The receptionist only sees the doctor on `receptionists.doctor_id`. Another doctor’s visits return 403.

### Admin

| Page | Route | Controller | View | Purpose |
| --- | --- | --- | --- | --- |
| Operations | `admin.dashboard` | `Admin\DashboardController@index` | `dashboard/admin/index.blade.php` | Counts and the approval queue |
| Doctors | `admin.doctors` | `DashboardController@doctors` | `dashboard/admin/doctors.blade.php` | Approve or reject |
| Users | `admin.users` | `DashboardController@users` | `dashboard/admin/users.blade.php` | Search and activate |
| Settings | `admin.settings` | `DashboardController@settings` | `dashboard/admin/settings.blade.php` | Horizon, notice, timezone, payment label |
| Notifications, security | shared account routes | | | |

There is no admin appointment browser and no admin patient chart. That avoids exposing clinical notes on the oversight screens.

## What already worked and was kept

- Role redirects from `/dashboard`.
- Weekly hours editor with day toggles, multiple periods, copy-to-days, and overlap validation.
- Date exceptions that refuse to block a range that already has a booked visit.
- Patient cancel and reschedule rules, including cancellation notice.
- Doctor visit status transitions, consultation notes, private report downloads, and messages.
- Referral snapshot, accept, decline, and schedule onto a real opening.
- Receptionist check-in from today’s queue.
- Database notifications and unread count in the shell.
- Pagination and filters on appointment lists.
- Guest booking draft restored after patient login.

## Problems this pass addressed

- Slot generation ignored visit length and buffer. See the scheduling section.
- The availability screen and the public booking form hardcoded India Standard Time. They now show `clinic_settings.timezone`.
- Admin, notifications, profiles, referrals, receptionist booking, and several doctor forms used an oversized `dash-banner` title. Those pages now use `x-ui.page-header` on the same shell as the doctor and patient home screens.
- The admin home was a row of unlabeled counts. It now leads with the approval queue and today’s status counts, all from the database.
- The booking slot panel used a 280px empty box before a date was chosen.

## Still limited

- Record access is enforced in controllers. Dedicated policies are not registered.
- Appointment lists still use a taller custom field style than `field`. Profile edit forms still use the older large input class.
- `dashboard/patient/book.blade.php` is unused by controllers.
- Messaging is not live. Notifications are in-app only.
- Receptionists cannot register a brand-new patient. They search existing patient accounts.
- There is no chart library. Today’s status is a count grid because that is enough to see the queue.
- `Appointment::upcoming()` and several dashboards still use the application `today()` / `now()` helpers. Slot generation uses the clinic timezone. If those two clocks diverge, “today” on a dashboard can differ from the booking clock until those queries are switched.
- True parallel HTTP requests were not run. Overlap protection is the doctor row lock plus the unique `active_slot` index, covered by a second booking of the same opening.

## Verification

Executed on 13 October 2026:

- `php artisan test` — 69 passed, 647 assertions.
- `npm run build` — Vite production build succeeded (`public/build/assets/app-BblV6EvD.css`, `public/build/assets/app-qzH1IZOV.js`).
- `php artisan view:cache` — run after the view edits.

New coverage is `tests/Feature/SlotAvailabilityTest.php`: duration slicing per doctor, buffer and partial leave, past times, a forged slot, a second booking of the same opening, a different opening, and reschedule releasing the previous time.

The pages were not clicked through in a browser in this pass. There is no browser automation tool in this session. Role access, booking, and the main dashboard screens are covered by the feature tests above.
