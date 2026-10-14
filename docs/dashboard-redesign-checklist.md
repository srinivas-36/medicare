# Medicare authenticated redesign checklist

Date: 13 October 2026.

Statuses:

- **Implemented** — this screen’s content arrangement was changed in source.
- **Functionally verified** — a feature test rendered the route, or the suite covered its main action, in this pass.
- **Visually verified** — compared in a browser against the Medicare reference. Not done in this pass. No browser automation was available.

A shared shell change is not treated as a full redesign by itself. Booking still uses `App\Services\AppointmentBooking`. Sample names and counts from the reference image were not copied into the app.

## Shared shell

| Piece | View | Status |
| --- | --- | --- |
| Dashboard layout, header search, account menu | `layouts/dashboard.blade.php`, `components/dashboard/account-menu.blade.php` | Implemented |
| Sidebar | `components/dashboard/sidebar.blade.php`, `config/dashboard.php` | Implemented |
| Page header, metric, avatar, panel, empty state | `components/ui/*` | Implemented |
| Design tokens | `resources/css/app.css` | Implemented |

Header search is omitted on `doctor.appointments` and `doctor.appointments.show` so that list does not gain a patient-name query field.

## Patient

| Screen | Route | View | Status |
| --- | --- | --- | --- |
| Overview | `patient.dashboard` | `dashboard/patient.blade.php` | Implemented. Functionally verified. |
| Find a doctor and book | `patient.book` | `dashboard/patient/doctors.blade.php`, `dashboard/patient/partials/book-form.blade.php` | Implemented. Functionally verified. Slots still come from the doctor’s saved `open` calendar. |
| Legacy book URL | `patient.book.create` | Redirects to `patient.book` | Functionally verified. No separate screen. |
| Appointments | `patient.bookings` | `dashboard/appointments/index.blade.php` | Implemented (denser title on the shared list). Functionally verified. |
| Appointment detail | `patient.bookings.show` | `dashboard/appointments/show.blade.php` | Implemented (compact sections). Functionally verified. |
| Medical history | `patient.history` | `dashboard/patient/history.blade.php` | Implemented. Functionally verified. |
| History detail | `patient.history.show` | `dashboard/patient/history-show.blade.php` | Implemented. Functionally verified. |
| Profile | `patient.profile.show` | `dashboard/patient/show.blade.php` | Implemented (compact cards). Functionally verified. |
| Edit profile | `patient.profile.edit` | `dashboard/patient/edit.blade.php` | Implemented (compact cards). Functionally verified. |
| Notifications | `account.notifications` | `account/notifications.blade.php` | Implemented. |
| Security | `account.security` | `account/security.blade.php` | Implemented. Password update covered by the workflow test. |

`dashboard/patient/book.blade.php` is still unused by controllers.

## Doctor

| Screen | Route | View | Status |
| --- | --- | --- | --- |
| Today | `doctor.dashboard` | `dashboard/doctor.blade.php` | Implemented. Counts are live queries. |
| Availability | `doctor.availability` | `dashboard/doctor/availability.blade.php`, `partials/weekly-schedule.blade.php` | Implemented. Functionally verified. |
| Appointments | `doctor.appointments` | `dashboard/appointments/index.blade.php` | Implemented. Functionally verified. |
| Visit | `doctor.appointments.show` | `dashboard/appointments/show.blade.php` | Implemented. Functionally verified. |
| Patients | `doctor.patients` | `dashboard/doctor/patients/index.blade.php` | Implemented. Functionally verified. |
| Add patient | `doctor.patients.create` | `dashboard/doctor/patients/create.blade.php` | Existing form kept. Functionally verified. |
| Patient chart | `doctor.patients.show` | `dashboard/doctor/patients/show.blade.php` | Existing chart kept (tabs, visits). Functionally verified. |
| Receptionists | `doctor.receptionists` | `dashboard/doctor/receptionists/index.blade.php` | Existing table kept. Functionally verified. |
| Add receptionist | `doctor.receptionists.create` | `dashboard/doctor/receptionists/create.blade.php` | Existing form kept. Functionally verified. |
| Referrals | `doctor.referrals` | `dashboard/doctor/referrals/index.blade.php` | Existing inbox table kept on the shared shell. |
| Referral detail | `doctor.referrals.show` | `dashboard/doctor/referrals/show.blade.php` | Implemented (compact cards). Functionally verified. |
| Profile | `doctor.profile.show` | `dashboard/doctor/profile/show.blade.php` | Implemented (compact cards). Functionally verified. |
| Edit profile | `doctor.profile.edit` | `dashboard/doctor/profile/edit.blade.php` | Implemented (compact cards). Functionally verified. |
| Notifications and security | shared account routes | | Implemented. |

## Receptionist

| Screen | Route | View | Status |
| --- | --- | --- | --- |
| Today’s queue | `receptionist.dashboard` | `dashboard/receptionist.blade.php` | Implemented. Search and status tabs filter today’s real queue. |
| Appointments | `receptionist.appointments` | `dashboard/receptionist/appointments.blade.php` | Implemented. Patient search is a real query. |
| Book | `receptionist.appointments.create` | `dashboard/receptionist/appointments/create.blade.php` | Existing booking form kept. Functionally verified. Uses `AppointmentBooking`. |
| Visit | `receptionist.appointments.show` | `dashboard/receptionist/appointments/show.blade.php` | Implemented. |
| Patients | `receptionist.patients` | `dashboard/receptionist/patients.blade.php` | Implemented. |
| Profile | `receptionist.profile` | `dashboard/receptionist/show.blade.php` | Implemented (compact cards). |
| Edit profile | `receptionist.profile.edit` | `dashboard/receptionist/edit.blade.php` | Implemented (compact cards). |
| Unassigned desk | same dashboard | `dashboard/receptionist/partials/unassigned.blade.php` | Implemented. Functionally verified. |

## Admin

| Screen | Route | View | Status |
| --- | --- | --- | --- |
| Operations | `admin.dashboard` | `dashboard/admin/index.blade.php` | Implemented. Headline counts, 7-day appointment counts, and today’s status mix are database queries. |
| Doctors | `admin.doctors` | `dashboard/admin/doctors.blade.php` | Implemented. Approve and reject unchanged. |
| Users | `admin.users` | `dashboard/admin/users.blade.php` | Implemented. Role and active/inactive filters added. |
| Settings | `admin.settings` | `dashboard/admin/settings.blade.php` | Implemented. Field names and defaults unchanged. |
| Notifications and security | shared account routes | | Implemented. |

There is still no admin appointment browser and no admin patient chart.

## Other authenticated screens

| Screen | Route | View | Status |
| --- | --- | --- | --- |
| Laravel profile | `profile.edit` | `profile/edit.blade.php` | Not moved into the Medicare shell. Role profile and security pages are the product screens. `ProfileTest` still covers this Breeze page. |
| Checkout success | `checkout.success` | `public/checkout/success.blade.php` | Public confirmation layout. Not part of the dashboard shell. |

## Verification this pass

- `php artisan test` — 69 passed, 648 assertions.
- `php artisan test --filter=MedicareWorkflowTest` — passed again after the history detail edit.
- `npm run build` — succeeded (`public/build/assets/app-D10iPi1y.css`, `public/build/assets/app-qzH1IZOV.js`).
- `php artisan view:cache` — succeeded. Compiled views were then cleared so local template edits are not frozen.

No page was opened in a browser, so none are marked visually verified.
