# 05 — Controllers and business logic

Controllers are classes in `app/Http/Controllers`. A public method is an action. Laravel calls it when a route matches. The method should decide what happens and what to return. In this project it also contains many queries. Services hold booking, files, referrals, Stripe, and AI. There is no action-class layer.

**Form request:** checks shape of input and, sometimes, a coarse role check.

**Policy:** only `AiAnalysisPolicy`. Other ownership checks are `abort_unless` inside the method.

**Model:** relationships and a few rules (`Appointment::applyStatus`, `canPatientCancel`, `canRefer`).

Read a method in this order: route, middleware, authorize/abort, validate, query, write, response.

## Base and view components

| Class | Role |
|---|---|
| `app/Http/Controllers/Controller.php` | Empty Laravel base |
| `App\View\Components\AppLayout` | Renders `layouts.app` |
| `App\View\Components\GuestLayout` | Renders `layouts.guest` |
| `App\View\Components\SectionHeading` | Renders `components.section-heading` |

## Auth controllers (`app/Http/Controllers/Auth`)

| Class | Method | Route | What it does |
|---|---|---|---|
| `RegisteredUserController` | `create` | GET `register` | View `auth.register` |
| `RegisteredUserController` | `store` | POST `register` | Transaction: user, profile or pending doctor, login, redirect `dashboard`. Deletes the photo if the transaction throws |
| `AuthenticatedSessionController` | `create` | GET `login` | View `auth.login` |
| `AuthenticatedSessionController` | `store` | POST `login` | `LoginRequest::authenticate`, session regenerate, booking-draft redirect or `dashboard` |
| `AuthenticatedSessionController` | `destroy` | POST `logout` | Logout, invalidate session, regenerate CSRF token |
| `PasswordResetLinkController` | `create`, `store` | forgot-password | Show form, send reset link |
| `NewPasswordController` | `create`, `store` | reset-password | Show form, save new password |
| `EmailVerificationPromptController` | `__invoke` | `verification.notice` | Prompt |
| `VerifyEmailController` | `__invoke` | `verification.verify` | Marks email verified if the hash matches |
| `EmailVerificationNotificationController` | `store` | `verification.send` | Resends the email |
| `ConfirmablePasswordController` | `show`, `store` | confirm-password | Confirms the password into the session |
| `PasswordController` | `update` | PUT `password` | Breeze password change. Dashboard uses `SecurityController` instead |

`RegisteredUserController::store` is the right example of a transaction. The user row and the profile row succeed or fail together. Without the transaction, a crash after `User::create` would leave an account with no patient profile.

## Public controllers

| Class | Method | Route | Rules |
|---|---|---|---|
| `Public\DoctorController` | `index` | `doctors.index` | Approved + active. Optional `query`. Paginate 9 |
| `Public\DoctorController` | `show` | `doctors.show` | Slug lookup. No approval filter |
| `Public\ReviewController` | `index` | JSON reviews | Public read |
| `Public\ReviewController` | `begin` | redirect into review UI | Needs a slug |
| `Public\ReviewController` | `store` | create or update review | Controller `abort_unless` role patient. Unique pair in the database |
| `Public\BookingController` | `create` | booking form | `formData` builds slots |
| `Public\BookingController` | `remember` | session draft | No row yet |
| `Public\BookingController` | `store` | create appointment | Patient form request. Delegates to `AppointmentBooking` |
| `Public\BookingController` | `success` | success page | Patient must own the appointment |
| `Public\StripeWebhookController` | `__invoke` | webhook | Signature, amount, currency `usd`, then paid+confirmed or cancel |

`BookingController::store` is a write endpoint. Validation is the form request (layer A). Role is middleware plus `authorize()` (layer B). Slot openness, horizon, leave, and payment policy are `AppointmentBooking` (layer C).

## Patient controllers

| Class | Method | Route | Ownership |
|---|---|---|---|
| `Patient\DashboardController` | `index` | `patient.dashboard` | Role check again inside the method |
| `Patient\BookDoctorController` | `index` | `patient.book` | Approved doctors only |
| `Patient\BookDoctorController` | `create` | `patient.book.create` | 403 if doctor not bookable. 404 if doctor has no user |
| `Patient\BookingController` | `index` | list | Only this patient’s appointments |
| `Patient\BookingController` | `show` | detail | `patient_id` match or 403 |
| `Patient\BookingController` | `storeReports` | upload | Owner, not cancelled, fewer than 24 reports |
| `Patient\BookingController` | `destroyReport` | delete | Owner + scoped binding |
| `Patient\BookingController` | `report` | download | Owner, file exists |
| `Patient\BookingController` | `messages` | JSON | Owner |
| `Patient\BookingController` | `storeMessage` | write message | Owner |
| `Patient\BookingController` | `cancel` | cancel | Owner and `canPatientCancel()` |
| `Patient\BookingController` | `reschedule` | move slot | Owner. `AppointmentBooking::reschedule` |
| `Patient\HistoryController` | `index` | history list | Patient’s visits. Optional doctor filter |
| `Patient\HistoryController` | `show` | one visit | `patient_id` match or 403 |

## Doctor controllers

| Class | Method | Route | Extra rule |
|---|---|---|---|
| `Doctor\DashboardController` | `index` | home | Aggregates this doctor’s visits |
| `Doctor\AvailabilityController` | `edit` | form | 404 if the user has no doctor row |
| `Doctor\AvailabilityController` | `update` | save week | Replaces time slots |
| `Doctor\AvailabilityController` | `storeException` | add block | Tied to this doctor |
| `Doctor\AvailabilityController` | `destroyException` | remove block | 404 if the exception is another doctor’s |
| `Doctor\AppointmentController` | `index` | list | This doctor’s visits. Can also stream in the return type; the list path returns a view |
| `Doctor\AppointmentController` | `show` | detail | 403 if `doctor_id` mismatches |
| `Doctor\AppointmentController` | `updateStatus` | status | 403 if not this doctor, then `applyStatus` |
| `Doctor\AppointmentController` | `updateConsult` | note | 403 unless status is confirmed, checked_in, or completed |
| `Doctor\AppointmentController` | `messages`, `storeMessage` | chat | Doctor must own the visit |
| `Doctor\AppointmentController` | `report` | download | Doctor owns visit, report belongs to visit, file exists |
| `Doctor\AiAnalysisController` | `show` | page | Policy manage |
| `Doctor\AiAnalysisController` | `forPatient` | patient analyses | Patient must be linked or have a visit |
| `Doctor\AiAnalysisController` | `store` | start job | `dispatchSync` of `GenerateAiAnalysis`. Not a queued hand-off |
| `Doctor\AiAnalysisController` | `status` | JSON | Analysis must belong to the appointment |
| `Doctor\AiAnalysisController` | `update` | clinician edit | Audit row |
| `Doctor\AiAnalysisController` | `approve` | approve | Audit row. Does not by itself change the visit note |
| `Doctor\PatientController` | `index`, `create`, `search`, `store`, `show`, `update` | roster | Show/update 404 unless linked or visited |
| `Doctor\ReceptionistController` | `index`, `create`, `store`, `destroy` | staff | Destroy checks `receptionist.doctor_id` |
| `Doctor\ReferralController` | `index` | list | This doctor’s sent and received |
| `Doctor\ReferralController` | `storePreview` | mock preview JSON | Recipient id must exist in `MockReferralDirectory` |
| `Doctor\ReferralController` | `preview` | sample page | 404 if the token is unknown |
| `Doctor\ReferralController` | `show` | real referral | Sender or recipient |
| `Doctor\ReferralController` | `store` | real send | `StoreReferralRequest` + `ReferralSender`. Visit must be this doctor’s and `canRefer()` |
| `Doctor\ReferralController` | `accept`, `decline` | answer | Recipient only, status `sent` |
| `Doctor\ReferralController` | `schedule` | book | Recipient, status `accepted` |
| `Doctor\ReferralController` | `report` | download | Involved doctor, report linked, file exists |
| `Doctor\PracticeController` | `consultations`, `services`, `reports`, `reviews` | practice pages | Scoped to this doctor |

`AiAnalysisController::store` calls `GenerateAiAnalysis::dispatchSync`. The HTTP request waits for the external API. The `queue:listen` process in `composer dev` does not run this job unless some other code dispatches it to the queue. Today the controller does not.

`GenerateClinicalAnalysis` is a second job. Application controllers do not dispatch it. Tests still call `GenerateClinicalAnalysis::dispatchSync`.

## Receptionist controllers

| Class | Method | Rule |
|---|---|---|
| `Receptionist\DashboardController@index` | Home for the assigned doctor’s day |
| `Receptionist\AppointmentController@index` | List that doctor’s visits. `range` query |
| `Receptionist\AppointmentController@create` | Booking form. Needs a doctor assignment |
| `Receptionist\AppointmentController@store` | `StoreReceptionistAppointmentRequest`. 403 if posted doctor id mismatches. Confirmed visit, not the patient Stripe form |
| `Receptionist\AppointmentController@show` | Same doctor only |
| `Receptionist\AppointmentController@updateStatus` | Cannot set `completed` (validation). Still passes through `applyStatus` |
| `Receptionist\AppointmentController@reschedule` | Same doctor. Booking service checks the slot |
| `Receptionist\PatientController@index` | Patients of the assigned doctor |
| `Receptionist\PatientController@search` | JSON. Role checked again |

## Admin

`Admin\DashboardController`:

| Method | Effect |
|---|---|
| `index` | Counts for today and a 7-day trend. No writes |
| `doctors` | Paginate 15, pending first |
| `approve` | `approved`, clear reason, notify |
| `reject` | Validate reason, set `cancelled`, notify |
| `users` | Search and filters. Invalid role/status query values are ignored |
| `updateUser` | Toggle `is_active`. Blocks self-deactivation and deactivating the last admin |
| `settings` / `updateSettings` | Single `clinic_settings` row via `ClinicSetting::current()` |

## Account

| Class | Method | Notes |
|---|---|---|
| `Account\ProfileController@show` | Patient or doctor show view based on role. Other roles 403 |
| `Account\ProfileController@edit` | Same split |
| `Account\ProfileController@updatePatient` | `UpdatePatientProfileRequest` |
| `Account\ProfileController@updateDoctor` | `UpdateDoctorProfileRequest`. 404 if no doctor row |
| `Account\ProfileController@showReceptionist`, `editReceptionist`, `updateReceptionist` | Receptionist-only. Update validates inline in the method |
| `Account\SecurityController@edit` | Security page |
| `Account\SecurityController@update` | Password + logout other devices |
| `Account\NotificationController@index` | List |
| `Account\NotificationController@read` | Mark one, redirect to payload URL |
| `Account\NotificationController@readAll` | Mark all read |

`NotificationController@read` redirects to a URL stored in the notification payload. That URL is written by `ClinicNotifier`, not by the patient. If a future notifier stores an external URL, this becomes an open redirect. Current payloads should be inspected in `ClinicNotifier` before treating the redirect as safe. The class builds internal route URLs in the methods read for this audit (`appointmentBooked` and the referral methods). Confirm any new payload the same way.

## ProfileController (Breeze)

`edit`, `update`, `destroy` at `/profile`. `destroy` deletes the authenticated user after `current_password`. Cascades are database foreign keys, not a soft delete. There is no `SoftDeletes` trait on `User`.

## Services worth knowing before editing a controller

| Service | Called by | Responsibility |
|---|---|---|
| `AppointmentBooking` | Public, patient, receptionist, doctor patient store, referral schedule | Slots, create, reschedule, price in cents, bookable horizon |
| `AppointmentReports` | Booking and later uploads | Stores files under `appointment-reports/{patientId}/{date}/{uuid}.ext` on the private disk |
| `ReferralSender` | `ReferralController::store` | Writes `referrals` and copies selected report links |
| `StripeCheckout` | Booking service | Creates a Checkout Session. Currency in the session payload is hard-coded `usd` |
| `ClinicNotifier` | Booking, referrals, admin review | `ClinicNotification` on the database channel |
| `ClinicalAnalysisService` and related AI classes | `GenerateAiAnalysis` | Calls the configured HTTP API and validates the JSON shape |
| `PatientHistory` | Context builder | Prior visits for the summary prompt |

`AppointmentBooking` is large. Slot methods (`openTimes`, `openSlots`, `assertOpen`, `visitSlots`, `confirmVisit`) are the ones to read before changing the calendar. Do not duplicate their checks in a controller.

## Transactions and failure halfway

| Place | Transaction? |
|---|---|
| Registration | Yes, `DB::transaction` |
| Stripe webhook appointment update | Read `StripeWebhookController::withAppointment` for the lock. The method loads by `client_reference_id` and updates one row |
| Status change | Single `save` inside `applyStatus` |
| Report upload | Files are stored first. `discard()` deletes them if a later validation throw happens inside `AppointmentReports::store` |
| Analysis | The job updates the analysis row. A provider failure is stored as a failed status in tests |

If you add a second write that must match the first, wrap both in `DB::transaction`. The unique `active_slot` index still protects double booking when two transactions race.

## Related

- Field tables: [06-validation-and-input-processing.md](06-validation-and-input-processing.md)
- Status graph: [09-business-workflows.md](09-business-workflows.md)
