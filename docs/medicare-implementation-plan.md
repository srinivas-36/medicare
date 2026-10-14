# Medicare implementation plan

This document records the Phase 1 audit and the implementation sequence for the existing Laravel 12 Medicare application. It is a working record of the codebase, not a replacement for it.

## Stack

- Laravel 12, PHP 8.2, Blade, Tailwind CSS 3.4, Vite, Alpine.js, session authentication, Sanctum installed but unused by the browser.
- Database: MySQL in the app environment, SQLite in-memory for PHPUnit.
- Roles already stored on `users.role`: `patient`, `doctor`, `receptionist`, `admin`.
- Role routes use `EnsureUserHasRole` (`role` middleware). `RoleMiddleware` is an empty leftover and is not registered.

## What already works

- Public pages, doctor directory, and doctor profiles for approved, active doctors.
- Patient, doctor, and receptionist dashboards with named routes and a config-driven sidebar.
- Weekly hours on `time_slots`, visit length, and buffer on `doctors`.
- Appointment overlap checks, doctor-row locking, and cancellation that frees a time.
- Consultations (one per appointment), AI analysis jobs that skip cleanly when no API key is set, appointment messages, and referral snapshots with selected report rows.
- Receptionist accounts assigned to one doctor, with desk booking limited to that doctor.
- Guest booking drafts stored on the private disk and restored after patient login.
- Password reset and the Breeze account password form at `/profile`.

## Gaps confirmed in code

- Public booking (`reserveWindow`) treats a weekly window such as 09:00–12:00 as one appointment and then redirects to Stripe. Reception and doctor booking use shorter slices. `openTimes()` still rejects dates outside 14 days while `bookableUntil()` allows through the end of next month.
- A paid or free visit can be marked `payment_status = paid` only because checkout or a zero fee succeeded.
- Sidebar entries `receptionist.referrals`, `receptionist.patients`, and `receptionist.profile` have no routes, so they render as unavailable.
- There is no administrator workspace, even though the `admin` role exists. Pending doctors cannot be approved in the UI.
- There is no date-specific leave or extra-hours model.
- Accepting a referral only changes status. It does not arrange a receiving appointment.
- Doctor-created visits in `Doctor\PatientController` do not queue visit analysis.
- Patient history is a per-doctor helper, not a patient timeline.
- Patient profile password changes do not require the current password.
- Medical reports are stored on the public disk.
- There is no in-app notification table.
- Receptionists can mark a visit completed from the board. Clinical notes have a save endpoint but no documentation form on the visit screen.

## Decisions

- One slot engine in `App\Services\AppointmentBooking` generates slices from weekly hours, exceptions, duration, buffer, notice, and the booking horizon. Public, patient, receptionist, doctor, and referral booking all use it.
- Default horizon is six months, stored on `clinic_settings` and editable by an administrator.
- New bookings are confirmed without Stripe. Historical `payment_status` values stay. A null status shows no payment label unless the clinic policy is `clinic`.
- Stripe classes and the webhook route stay isolated and are not called by booking.
- New report files go to the private `local` disk. Older public files remain readable through authorized downloads and are not deleted.
- Cancelled visits release the slot. No-show visits keep it. Existing appointments are never cancelled automatically when a doctor blocks a day.
- Receptionists do not see AI analysis, clinical notes, or appointment messages.

## Phases

1. Audit and this document.
2. Shared availability, horizon, exceptions, overlap lock, and removal of Stripe from booking.
3. Teal design tokens on the existing Tailwind 3 theme, plus the screens needed for the new workflows.
4. Public, receptionist, and doctor booking on the shared engine, including reschedule, check-in, and no-show.
5. Patient history, consultation form, and private report access.
6. Referral scheduling onto a real slot.
7. Account security, receptionist profile, doctor approval, clinic settings, and in-app notifications.
8. Feature tests and `php artisan test`.

## Implementation status

The gaps above are the pre-change audit. The following is now in the application.

- `AppointmentBooking` is the shared slot engine. Public, patient, receptionist, doctor, and referral booking reserve a visit-length slice, confirm it, and leave `payment_status` null. Stripe checkout is no longer constructed during booking.
- Booking horizon defaults to six months through `clinic_settings`. Past times, dates beyond the horizon, leave, buffer, and overlapping occupied visits are rejected. Cancelled visits clear `active_slot`. A unique `active_slot` plus a locked doctor row blocks a second booking of the same start.
- Doctors manage weekly hours and date exceptions. Blocking a day that already has appointments is refused, and those visits stay booked.
- Patients have a chronological history. A later consultation does not replace an earlier one.
- Doctors document a consultation on the visit page. Saving notes does not complete the visit. AI analysis stays labeled as assistance and still runs only when an API key is configured.
- New reports are stored on the private disk. Authorized downloads still serve older public files.
- An accepted referral can be scheduled onto an open slice. The new appointment stores `referral_id`.
- Every role can change a password from Account security. The current password is required, and other sessions are logged out before the new password is saved.
- Administrators can approve or reject doctors, activate or deactivate users, and edit clinic booking policy.
- In-app notifications use the database channel. Missing mail configuration does not affect booking.

### Migration

`database/migrations/2026_10_13_190000_add_clinic_operations.php`

Adds `clinic_settings`, `doctor_availability_exceptions`, `appointment_reschedules`, and `notifications`. Extends appointments, referrals, consultations, doctors, and appointment messages. Existing appointment rows are kept. Duplicate legacy slot keys are left without `active_slot` rather than deleted.

### Tests executed

`php artisan test` — 65 passed, 599 assertions (2026-10-13).

Covered: slice booking without Stripe, horizon and leave rejection, double booking, private history that is not overwritten, admin approval, and password change that rejects a wrong current password. Existing booking, conversation, referral, receptionist, and profile tests also passed.

### Still incomplete

- Record-level access is enforced in controllers. Dedicated policies are not registered.
- The teal theme is applied through existing Tailwind tokens. Older screens still contain hardcoded blue values, and the full public marketing layout was not rebuilt.
- Receptionists can search patients. They cannot register a brand-new patient from the desk.
- Messaging refreshes from the database. It is not broadcast live.
- Notifications are in-app only.
- The appointment screens were not clicked through in a browser during this pass.

## Verify

```bash
php artisan migrate
php artisan test
npm run build
```
