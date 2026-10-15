# 06 — Validation and input processing

Validation answers: “Is this submission shaped correctly?” It does not answer “May this person do it?” or “Does the clinic allow this status?” Those are chapters 08 and 09.

## Where rules live

| Place | Examples |
|---|---|
| Form request `rules()` | Registration, booking, profiles, referrals, reports, messages, reviews, analysis, availability, consultation, status, cancel |
| Form request `authorize()` | Coarse role check. A `false` result is 403 before rules run |
| `withValidator` | Availability overlaps, report file required, doctor profile dates, analysis report ids |
| `$request->validate()` in a controller | Admin settings, receptionist profile, reschedule, referral decline/schedule, availability exceptions, booking draft, password reset |
| Database | `unique` emails, unique `active_slot`, foreign keys, MySQL enums |
| Browser | `required` attributes and the submit-button lock in `resources/js/app.js`. Never trusted |

There is no `app/Rules` class. Custom checks are closures or `after` hooks.

## What happens when validation fails

1. Laravel throws `ValidationException`.
2. For a normal form, the exception handler redirects to the previous URL (302).
3. Errors are stored in the session error bag `default`, unless the code uses `validateWithBag` (`userDeletion`, `updatePassword`).
4. Old input is flashed. Passwords and files are not flashed back.
5. The next GET renders the form. `@error('email')` or `$errors->first('email')` prints the message. `old('email')` restores the text.
6. A JSON client receives 422 and an `errors` object. No redirect.

Nothing is written to the clinic tables when this happens before the controller body. A rule inside the controller after some files were stored must delete those files. `AppointmentReports::discard` does that for the upload helper.

## Rule meanings used in this repository

| Rule | Meaning here |
|---|---|
| `required` | The field must be present and non-empty |
| `nullable` | May be missing or empty. Other rules are skipped when empty |
| `required_if:status,cancelled` | Cancel reason is required only when status is cancelled |
| `required_with:other_reports` | The group name is required when those files are uploaded |
| `sometimes` | Not used in the form requests that were read |
| `string`, `integer`, `numeric`, `boolean`, `date` | Type checks |
| `date_format:H:i` | Time must look like `09:30` |
| `email`, `lowercase` | Email shape. Registration also adds a regex |
| `confirmed` | Needs `password_confirmation` |
| `unique:users,email` | No other user has that email |
| `exists` | The id must be a row. Referral targets must be an approved doctor id that is not the sender |
| `Rule::in([...])` | Only those exact values |
| `image` | Uploaded image. Registration messages say JPG or PNG. The rule itself accepts Laravel’s image set |
| `mimes:jpg,jpeg,png,gif,webp,pdf` | Report files |
| `max:10240` on files | 10240 kilobytes, about 10 MB, on the patient booking upload |
| `max:5120` on later report uploads | About 5 MB. The two upload paths do not use the same limit |
| `array` and `field.*` | Lists of files or slot rows |
| `Password::defaults()` | Framework minimum of 8 characters, because `AppServiceProvider` does not customize it |
| `current_password` | Must match the logged-in user’s password |
| `timezone` | Admin clinic timezone must be a PHP timezone |

Frontend `required` can be bypassed by posting directly. Server rules are the ones that count.

## Registration — POST `/register`

Class: `app/Http/Requests/Auth/RegisterRequest.php`. `authorize()` returns true. The `guest` middleware already blocks logged-in users.

| Field | Required | Rules | Database | Error behavior |
|---|---|---|---|---|
| name | yes | string, max 255, letters with spaces, apostrophes, hyphens | `users.name` | Redirect back, custom message |
| email | yes | email, lowercase, regex, unique | `users.email` unique | Redirect back |
| password | yes | confirmed, Password::defaults, regex letter and digit, min 8 from the regex | `users.password` hashed cast | Redirect back |
| phone | yes | exactly 10 digits | `users.phone` nullable in the table, required by the form | Redirect back |
| age | yes | exactly 2 digits as a string | `patient_profiles.age` for patients. Not a column on `users` | Redirect back |
| role | yes | `patient` or `doctor` | `users.role` enum also allows receptionist and admin, which this form refuses | Redirect back |
| gender | yes | male, female, other | `users.gender` enum, nullable in the table | Redirect back |
| photo | no | image, max 2048 KB | stored path on `users.photo` | Redirect back |

`prepareForValidation` trims name, phone, and age.

## Login — POST `/login`

`email` required email string. `password` required string. No “user exists” vs “wrong password” distinction: both use `auth.failed`. Five failures per email and IP lock the key. `remember` is not in `rules()`; `boolean('remember')` reads it without a rule.

## Patient booking — POST `/doctors/{doctor}/book`

`StorePatientAppointmentRequest`. Authorize: role patient.

| Field | Required | Rules |
|---|---|---|
| appointment_date | yes | date, today through `AppointmentBooking::bookableUntil()` |
| slot_day | yes | string max 20 |
| starting_time, ending_time | yes | `H:i` |
| health_issues | no | string max 2000 |
| symptom_duration | no | string max 80 |
| current_medicines | no | string max 500 |
| blood_reports, xray_reports, other_reports | no | arrays, max 8 each |
| each file | if present | jpg, jpeg, png, gif, webp, pdf, max 10240 KB |
| other_report_name | with other files | string max 120 |

Passing these rules does not reserve the slot. `AppointmentBooking::assertOpen` or `assertWindow` runs next. A taken slot returns a validation error from the service. The unique `active_slot` column is the race backstop.

## Booking draft — POST `/doctors/{doctor}/book/continue`

Inline rules in `BookingController::remember`. Fields are nullable. Files use the same 10 MB mime list. The draft is stored in the session, not in a table. Files are stashed by `stashDraftFiles`. This route is public. Do not put secrets in the draft.

## Later report upload — POST patient bookings reports

`StoreAppointmentReportsRequest`. Same groups, **max 5120 KB**, and `withValidator` requires at least one file. Controller then rejects cancelled visits and a 24-report cap.

## Appointment status

`UpdateAppointmentStatusRequest`. Authorize: doctor or receptionist.

| Field | Doctor | Receptionist |
|---|---|---|
| status | confirmed, checked_in, completed, cancelled, no_show | confirmed, checked_in, cancelled, no_show |
| cancel_reason | required if cancelled, max 255 | same |

`Appointment::applyStatus` can still reject the value. Receptionists never pass the `completed` string through this request. Doctors can pass `completed` only when the current status allows it.

## Cancel (patient)

`CancelAppointmentRequest`. `cancel_reason` nullable string max 255. `canPatientCancel()` is a separate 422.

## Reschedule (patient and receptionist)

Inline: `date` required date, `time` required `H:i`, `reason` nullable max 255. A `slot` value `YYYY-MM-DD|HH:MM` is split before validation. The booking service checks the window.

## Messages

`StoreAppointmentMessageRequest`. `body` required string max 2000. Authorize is in the request class; the controller also checks ownership. Whitespace-only bodies fail `required` after trimming middleware (`TrimStrings` does not strip the interior; a body of spaces may pass `required`). The conversation test posts three spaces and expects a session error. If that part of the test failed, the failure output did not isolate it from the later `assertSee`.

## Consultation note

`UpdateConsultationRequest`. All clinical fields nullable. Lengths: notes, plan, and follow-up instructions max 20000. Diagnosis max 255. `follow_up_on` nullable date. Authorize: doctor. Controller also requires a visit status that allows a note.

## Referral send

`StoreReferralRequest`.

| Field | Rules |
|---|---|
| to_doctor_id | required integer, exists approved doctors, not the sender, active doctor user |
| reason | required string max 5000 |
| clinical_question | nullable max 5000 |
| urgency | nullable `routine`, `soon`, `urgent` |
| report_ids.* | integer |

Preview POST uses a different inline rule: `to_doctor_id` required string, then a lookup in `MockReferralDirectory`. That is not the real doctor table.

Decline: `decline_reason` nullable max 2000.

Schedule: `date` and `time` only, after the status check.

## Doctor adds a patient

`StoreDoctorPatientRequest`.

| Field | New patient | Existing mode |
|---|---|---|
| mode | `new` or `existing` or omitted | `existing` |
| patient_id | optional | required, exists users where role is patient |
| name, email, phone, age | required | nullable |
| age | 2-digit string | |
| gender | optional enum | |
| blood_type | optional ABO set | |
| password | required unless the email is already a patient | nullable |
| date, time, visit_type | required | required |
| reason | optional max 255 | |

If the email belongs to a non-patient, the controller adds an error after validation. Age on the profile is the two-digit string. The patient profile update elsewhere uses an integer 0–120. The two forms do not share one age rule.

## Receptionist books

`StoreReceptionistAppointmentRequest`. Authorize: receptionist with a `doctor_id`.

`patient_mode` existing or new. New patients need name, email, phone, age integer 0–120, and a password unless that email is already a patient. Slot must match `HH:MM|HH:MM`. Date uses the same horizon helper.

## Profiles

**Patient** `UpdatePatientProfileRequest`: name, phone max 30, age integer 0–120, optional gender, blood type, photo, and nested bp/diabetic/hyperthyroidism flags.

**Doctor** `UpdateDoctorProfileRequest`: name, phone 10 digits or empty, ticket price numeric min 0 with a decimal regex, bio, about, qualifications, experiences, time slots, duration and buffer from fixed lists. `withValidator` checks date order and slot overlaps. `approval_status` is not a rule, so it is dropped from `validated()`.

**Receptionist** inline: name required, phone nullable max 30, gender enum, photo image 2 MB.

**Breeze** `ProfileUpdateRequest`: name and email, unique ignoring the current user.

## Availability

`UpdateDoctorAvailabilityRequest`: `timeSlots.*.day` in the class day list, start and end `H:i`, `isAvailable` boolean, duration in 15, 20, 30, 45, 60, 90, buffer in 0, 5, 10, 15, 20, 30.

Exception inline: title, kind `unavailable|extra|override`, dates, optional times. Extra and override need a real time range. Unavailable is rejected when visits already exist in that window.

## Reviews

`StoreDoctorReviewRequest`: rating integer 1–5, `reviewText` required max 1000. Throttle is on the route, not in the rules.

## Analysis

`StoreAiAnalysisRequest`: `report_ids` array of integers. After-hook: every id must be a report on this appointment, and the extractor must support the file. Authorize uses `AiAnalysisPolicy`.

`UpdateAiAnalysisReviewRequest`: `clinician_summary` required max 8000, `doctor_notes` nullable max 4000, `reviewed` array of integers.

## Admin

| Endpoint | Fields |
|---|---|
| Reject doctor | `rejection_reason` required string max 1000 |
| Update user | `is_active` required `0` or `1` |
| Settings | horizon 1–24, minimum notice 0–10080 minutes, cancellation notice 0–168 hours, payment policy `none` or `clinic`, timezone |

## Passwords

| Endpoint | Fields | Bag |
|---|---|---|
| `account.password.update` | current_password, password confirmed, Password::defaults | default |
| PUT `/password` | same | `updatePassword` |
| DELETE `/profile` | password current_password | `userDeletion` |
| Reset | token, email, password confirmed | default |
| Forgot | email | default |

`User` casts `password` to `hashed`. The framework does not hash a second time when the value is already a bcrypt hash (`HasAttributes::castAttributeAsHashedString`).

## Database constraints that validation does not replace

| Constraint | What it catches |
|---|---|
| `users.email` unique | Two requests registering the same email |
| `appointments.active_slot` unique | Two active bookings of the same doctor, date, and start time |
| `doctor_patients` unique pair | Linking the same patient twice |
| `doctor_reviews` unique pair | A second insert instead of an update |
| Foreign keys | A doctor id that does not exist |
| MySQL status enum | A status string the column does not list, on MySQL only. The migration alters the enum only when the driver is mysql |

SQLite tests do not enforce MySQL enums. A status that works in PHPUnit can still be rejected by MySQL if the migration was not applied. On this machine `migrate:status` shows that migration as Ran.

## Related

Invalid-submit sequence: [02-request-lifecycle.md](02-request-lifecycle.md). Status graph: [09-business-workflows.md](09-business-workflows.md).
