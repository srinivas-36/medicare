# 07 — Database schema and Eloquent

**Active schema:** `php artisan migrate:status` on 14 October 2026 showed all 24 migration files as **Ran** against the MySQL database named by the local environment. This chapter does not print row contents. No patient records were queried.

**Tests** use SQLite in memory (`phpunit.xml`). SQLite does not apply the MySQL-only `ALTER TABLE ... ENUM` in `2026_10_13_190000_add_clinic_operations.php`. Status values that PHP accepts can differ between the test database and MySQL.

## Words

A **table** is a named grid. A **row** is one record. A **column** is a field. A **primary key** here is `id` on almost every table (`notifications.id` is a UUID). A **foreign key** points at another table and, in these migrations, usually deletes or nulls the child when the parent goes away (`cascadeOnDelete` or `nullOnDelete`).

An **Eloquent model** is a PHP class. `Appointment::query()->where(...)` becomes SQL. A relationship method such as `patient()` does not run SQL until you use it.

## Migration versus the live database

A migration file is a recipe. It changes the database only when `php artisan migrate` runs. This audit did not run migrate. Status shows the recipes have already been applied locally. A new clone is different until someone migrates it.

## Tables

| Table | Migration file | Model |
|---|---|---|
| `users` | `0001_01_01_000000_create_users_table.php` plus slug, receptionist move | `User` |
| `password_reset_tokens` | same | framework |
| `sessions` | same | framework |
| `cache`, `cache_locks` | `0001_01_01_000001_create_cache_table.php` | none |
| `jobs`, `job_batches`, `failed_jobs` | `0001_01_01_000002_create_jobs_table.php` | none |
| `doctors` | `2026_10_07_071333_create_doctors_table.php` plus settings and rejection reason | `Doctor` |
| `doctor_qualifications` | `2026_10_07_071937_create_doctor_qualifications_table.php` | `DoctorQualification` |
| `doctor_experiences` | `2026_10_07_072008_create_doctor_experiences_table.php` | `DoctorExperience` |
| `patient_profiles` | `2026_10_07_072256_create_patient_profiles_table.php` plus health notes | `PatientProfile` |
| `personal_access_tokens` | `2026_10_09_065225_create_personal_access_tokens_table.php` | Sanctum |
| `time_slots` | `2026_10_11_090005_create_time_slots.php` | `TimeSlot` |
| `appointments` | `2026_10_11_151000_create_appointments_table.php` plus checkout, reason text, clinic columns | `Appointment` |
| `appointment_reports` | `2026_10_11_203500_add_checkout_to_appointments.php` | `AppointmentReport` |
| `appointment_messages` | `2026_10_13_160000_create_appointment_messages_table.php` plus `read_at` | `AppointmentMessage` |
| `consultations` | `2026_10_12_110200_create_consultations_and_referrals.php` plus follow-up columns | `Consultation` |
| `referrals` | same plus urgency and decline columns | `Referral` |
| `referral_reports` | same | `ReferralReport` |
| `doctor_patients` | `2026_10_13_120000_create_doctor_patients_table.php` plus `added_by` | pivot, no model class |
| `clinic_settings` | `2026_10_13_190000_add_clinic_operations.php` | `ClinicSetting` |
| `doctor_availability_exceptions` | same | `DoctorAvailabilityException` |
| `appointment_reschedules` | same | `AppointmentReschedule` |
| `notifications` | same | framework `DatabaseNotification` |
| `receptionists` | `2026_10_13_200000_move_receptionist_assignment_to_receptionists_table.php` | `Receptionist` |
| `doctor_reviews` | `2026_10_14_001500_create_doctor_reviews_table.php` | `DoctorReview` |
| `ai_analyses` | `2026_10_14_140000_create_ai_analyses_tables.php` | `AiAnalysis` |
| `ai_analysis_audits` | same | `AiAnalysisAudit` |

`2026_10_13_170000_add_assigned_doctor_id_to_users_table.php` also creates `receptionists`. The later migration moves assignment onto that table. Read both before changing the receptionist relationship. The live database has the later shape because both Ran.

## `users`

Verified columns from the create migration and later edits: `id`, `name`, `email` unique, `email_verified_at` nullable, `password`, `phone` nullable, `photo` nullable, `gender` enum `male|female|other` nullable, `role` enum `patient|doctor|receptionist|admin` default `patient`, `is_active` boolean default true, `remember_token`, timestamps, `slug` unique from `2026_10_08_120958_add_slug_to_users_table.php`.

Indexes on email, gender, and role exist from the create migration. Email is both unique and indexed.

**Mass assignment:** `$fillable` includes `role` and `is_active`. Controllers that call `User::create` with a hand-built array set `role` explicitly to `patient` or `receptionist`. A future `$request->all()` into `create` would be able to set `role` because it is fillable. Profile update uses `ProfileUpdateRequest`, which only validates name and email, then `fill($request->validated())`. That path cannot set `role`.

**Hidden:** `password`, `remember_token`.

**Casts:** `email_verified_at` datetime, `password` hashed, `is_active` boolean.

**Relationships:**

| Method | Type | Points at |
|---|---|---|
| `doctor()` | hasOne | `doctors.user_id` |
| `receptionist()` | hasOne | `receptionists.user_id` |
| `patient_profile()` | hasOne | `patient_profiles.user_id` |
| `doctorReviews()` | hasMany | `doctor_reviews.user_id` |
| `appointmentsAsPatient()` | hasMany | `appointments.patient_id` |
| `consultationsAsPatient()` | hasMany | `consultations.patient_id` |
| `referralsAsPatient()` | hasMany | `referrals.patient_id` |

`uniqueSlug` generates a URL slug from the name and appends `-2`, `-3`, and so on while the slug exists.

## `doctors`

One row per user (`user_id` unique, cascade delete). Columns verified from the create migration: `specialization` nullable, `ticket_price` decimal default 0, `bio`, `about`, `approval_status` enum `pending|approved|cancelled` default `pending`, `average_rating`, `total_rating`. Later migrations add appointment duration and buffer (`2026_10_13_163000_add_appointment_settings_to_doctors_table.php`) and `rejection_reason`.

`getAttribute` redirects `name`, `email`, `phone`, `photo`, and `gender` to the related user, and maps `ticketPrice`, `isApproved`, `averageRating`, `totalRating`. Reading `$doctor->name` lazy-loads `user` if it was not eager-loaded. That is an easy N+1 if you loop doctors and print names without `with('user')`.

**Relationships:** `user`, `receptionists`, `experiences`, `qualifications`, `timeSlots`, `availabilityExceptions`, `appointments`, `reviews`, `registeredPatients` (belongsToMany `users` through `doctor_patients` with pivot `origin` and `added_by`), `consultations`, `referralsSent` (`from_doctor_id`), `referralsReceived` (`to_doctor_id`).

## `appointments`

Create-migration columns: `patient_id` and `doctor_id` cascade delete, `appointment_date`, `appointment_time`, `status` originally `pending|confirmed|completed|cancelled`, `reason`, `visit_type` `check-up|consultation|follow-up|emergency`, `notes`, `cancelled_by` null on delete, `cancel_reason`, timestamps.

Later columns verified in migrations: checkout fields `ticket_price`, `payment_status`, `stripe_session_id` unique, `stripe_payment_intent`, `paid_at` (`2026_10_11_203500`), `ending_time` is in the model fillable (confirm the checkout migration if you need the exact line), `source`, `referral_id`, `rescheduled_from_id`, `active_slot` unique nullable (`2026_10_13_190000`). MySQL status enum becomes `pending|confirmed|checked_in|completed|cancelled|no_show`.

`reason` is changed to text by `2026_10_13_180000_make_appointment_reason_text.php`.

**`active_slot`:** on save, if status is not `cancelled` and doctor, date, and time are present, the model sets `active_slot` to `doctorId|Y-m-d|normalizedTime`. Cancelled rows set it to null. The unique index blocks two live rows with the same slot. This is the concurrency control. It is not a transaction around the whole booking conversation, but the insert fails if the slot was taken.

**Relationships:** `patient`, `bookedBy`, `doctor`, `reports`, `messages`, `consultation` hasOne, `analyses`, `referrals`, `cancelledBy`, `referral`, `reschedules`.

**Scopes:** `scopeUpcoming` is date after today, or today with time still ahead. It uses `today()` and `now()`, which follow the application timezone (UTC in `about`).

**Not soft-deleted.** Deleting a user cascades appointments.

## Status values

Allowed transitions are in `Appointment::applyStatus`, not in the database:

```164:174:app/Models/Appointment.php
        $allowed = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['checked_in', 'completed', 'cancelled', 'no_show'],
            'checked_in' => ['completed', 'cancelled', 'no_show'],
        ];
```

`completed` and `cancelled` cannot move. `no_show` has no outgoing list, so a later change is rejected. There is no transition back to `pending`.

`payment_status` is a string, not an enum in the migration that adds it (`nullable` string). Code sets `paid`, `unpaid`, and `failed`.

`doctors.approval_status`: `pending` → `approved` or `cancelled`. The admin reject action writes `cancelled`.

## Other clinic tables (verified intent)

| Table | Integrity |
|---|---|
| `consultations` | `appointment_id` unique. One note row per visit |
| `referrals` | From doctor, to doctor, patient, appointment. Status changes in `ReferralController` (`sent`, `accepted`, `declined` are the values that controller checks) |
| `referral_reports` | Unique pair of referral and report |
| `doctor_patients` | Unique `(doctor_id, patient_id)` |
| `doctor_reviews` | Unique `(doctor_id, user_id)` |
| `appointment_reports` | Belongs to an appointment. Path is a private disk path |
| `time_slots` | Weekly rows for a doctor: day, start, end, availability flag |
| `clinic_settings` | One seeded row. Horizon months default 6, cancellation hours default 0, payment policy default `none` |
| `ai_analyses` | Belongs to appointment, doctor, patient. Status helpers on the model: `isOpen`, `isRunning`, `isStale` |
| `ai_analysis_audits` | Who changed a summary |

Read each migration file before adding a column. This chapter does not restate every nullable flag that was not opened line by line.

## Eager loading and N+1

`DoctorController@index` uses `with(['user', 'qualifications', 'timeSlots'])`. That is eager loading: one query for doctors and extra queries for the relations, not one query per doctor.

`Doctor::getAttribute` for `name` uses `$this->user`. If a loop forgot `with('user')`, each name is a query. That is an N+1. Fix it at the query with `with('user')`, not in the Blade file.

Lazy loading is allowed. The app does not call `Model::preventLazyLoading()`.

## Mass assignment

`$fillable` is the allow list. `Appointment` fillable includes `patient_id`, `status`, `payment_status`, and Stripe ids. Controllers should pass explicit arrays. `forceFill` is used by the Stripe webhook to set payment fields even if a future developer removed them from fillable. `forceFill` skips the allow list. It is appropriate for that trusted webhook path and dangerous on request input.

`guarded` is not used. There is no `$guarded = ['*']`.

## Casts

`Appointment` casts `appointment_date` to a date and `ticket_price` to `decimal:2`, `paid_at` to datetime. `Doctor` casts prices and durations. `User` casts `is_active` and `password`.

Accessors are not a separate `Attribute::make` API on `Appointment`. `Doctor::getAttribute` is an override. Be careful: unknown keys still call `parent::getAttribute`.

## Transactions and locks

| Operation | Mechanism |
|---|---|
| Registration | `DB::transaction` |
| Doctor-created patient plus profile | `DB::transaction` in `PatientController::store` |
| Stripe update | `DB::transaction` and `lockForUpdate()` on the appointment row |
| Booking slot | Unique `active_slot` plus PHP checks in `AppointmentBooking` |

`lockForUpdate` matters when two webhook deliveries arrive together. The second waits, then sees `payment_status === 'paid'` and returns.

## Pagination

Directory pages use `paginate(9)`, `paginate(12)`, `paginate(15)`, or `paginate(20)` depending on the controller. Appointment lists are covered by tests that expect ten rows per page. The paginator’s Blade views are in Tailwind’s content paths so pagination classes are generated.

## Factories and seeders

Only `database/factories/UserFactory.php` exists. Tests create doctors and appointments with helpers inside the test classes, not with a full factory set. `database/seeders/DatabaseSeeder.php` is the seeder entry. This audit did not run it.

## Related

Workflows that write these tables: [09-business-workflows.md](09-business-workflows.md).
