# Doctor appointment system — implementation guide

This guide explains the appointment work in this Laravel project: what was already there, what was added, why each piece exists, and how the pieces connect. It is written so you can rebuild the same feature yourself.

The app is server-rendered. Screens are Blade forms and controllers. There is no Inertia, Livewire, React, Vue, or JSON API for these pages.

---

## 1. The idea in one sentence

A visit is one row in the `appointments` table: one patient, one doctor, one date, one time, and a status. Registration, profiles, and time slots exist so that row can be created safely and shown on screens that were already designed.

---

## 2. Words you need

| Word | Meaning in this project |
| --- | --- |
| Route | A URL plus a name, for example `GET /patient/bookings` named `patient.bookings`. Blade calls `route('patient.bookings')` to build the link. |
| Middleware | Code that runs before the controller. `auth` means "must be logged in". `role:patient` means "the user's `users.role` must be patient". |
| Form Request | A class that checks the submitted form. If something is wrong, Laravel sends the user back to the form with error messages and the values they typed. |
| Model | A PHP class that reads and writes one database table. `Appointment` talks to `appointments`. |
| Relationship | A method that connects tables. `Doctor::timeSlots()` means "the rows in `time_slots` that belong to this doctor". |
| Blade | The HTML templates in `resources/views`. They run on the server and send finished HTML to the browser. |
| Session flash | A one-time message stored in the session, such as `session('status')`. The next page shows it, then it disappears. |
| snake_case | Database style: `ticket_price`, `starting_time`. |
| camelCase | Form style already used in the Blade files: `ticketPrice`, `startingTime`. |

A normal successful request travels like this:

```text
Browser submits a form
  -> routes/web.php matches the URL and the route name
  -> auth middleware checks the login
  -> role middleware checks patient / doctor / receptionist
  -> Form Request validates the fields
  -> Controller checks that this user is allowed to touch this row
  -> Model or AppointmentBooking writes the database
  -> Controller redirects to another named route with session('status')
  -> The next Blade page reads the database and draws the cards
```

If validation fails, the controller never runs. Laravel redirects back. `@error('fieldName')` under each input prints the message. `old('fieldName')` puts the typed value back in the box.

---

## 3. What was already built (leave it alone)

These pieces were finished before the appointment work. The new code plugs into them. It does not restyle the public marketing site.

Layouts:

- `resources/views/layouts/public.blade.php` — home, doctors, login, register, and the book page
- `resources/views/layouts/dashboard.blade.php` — every logged-in screen

Public pages that were not changed: home, about, contact, services, faq, security.

Auth views that were kept: `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`.

Dashboard shells that already had the card design, with fake names such as John Smith:

- `resources/views/dashboard/doctor.blade.php`
- `resources/views/dashboard/patient.blade.php`
- `resources/views/dashboard/receptionist.blade.php`

Profile screens that already had the fields and the card layout:

- `resources/views/dashboard/doctor/profile/show.blade.php`
- `resources/views/dashboard/doctor/profile/edit.blade.php`
- `resources/views/dashboard/doctor/partials/qualification.blade.php`
- `resources/views/dashboard/doctor/partials/experience.blade.php`
- `resources/views/dashboard/doctor/partials/time-slot.blade.php`
- `resources/views/dashboard/patient/show.blade.php`
- `resources/views/dashboard/patient/edit.blade.php`

The sidebar (`resources/views/components/dashboard/sidebar.blade.php`) reads `config/dashboard.php`. For each link it checks `Route::has('some.name')`. If the name exists, it renders a real `<a>`. If the name does not exist, it shows the word "Soon". You turn a sidebar item on by registering that route name. You do not edit the sidebar component for that.

Visual classes already used, and reused on the new pages:

- Page hero: `rounded-2xl bg-[#0067FF] p-6 text-white shadow-[0_12px_40px_rgba(0,103,255,0.22)]`
- Card: `rounded-2xl border border-[#0066ff1f] bg-white shadow-[0_12px_40px_rgba(0,103,255,0.08)]`
- Input: `h-12 w-full rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4`
- Primary button: `inline-flex h-12 items-center justify-center rounded-xl bg-[#0067FF] px-6 font-semibold text-white`
- Status pills: Confirmed green, Pending amber, Completed blue, Cancelled red
- Success message: `session('status')` in the green banner already on the profile pages
- Errors: `@error` with `text-sm text-red-600`

Roles live on `users.role`: `patient`, `doctor`, `receptionist`, `admin`.

The role check is `App\Http\Middleware\EnsureUserHasRole`, registered in `bootstrap/app.php` as the alias `role`. A route written as `role:doctor` only continues when the logged-in user's role is exactly `doctor`.

---

## 4. Why the forms and the database use different names

The edit forms were already written. Their inputs look like this:

```html
<input name="ticketPrice">
<input name="timeSlots[0][startingTime]">
<input name="qualifications[0][university]">
<input name="experiences[0][position]">
<input name="bloodType">
```

MySQL columns in this project use snake_case. The controller is the translator.

| Form field | Database column |
| --- | --- |
| `ticketPrice` | `doctors.ticket_price` |
| `timeSlots[n][day]` | `time_slots.day` |
| `timeSlots[n][startingTime]` | `time_slots.starting_time` |
| `timeSlots[n][endingTime]` | `time_slots.ending_time` |
| `timeSlots[n][isAvailable]` | `time_slots.is_available` |
| `qualifications[n][degree]` | `doctor_qualifications.degree` |
| `qualifications[n][university]` | `doctor_qualifications.institution` |
| `qualifications[n][startingDate]` | `doctor_qualifications.start_date` |
| `qualifications[n][endingDate]` | `doctor_qualifications.end_date` |
| `experiences[n][position]` | `doctor_experiences.designation` |
| `experiences[n][hospital]` | `doctor_experiences.hospital_name` |
| `experiences[n][startingDate]` | `doctor_experiences.start_date` |
| `experiences[n][endingDate]` | `doctor_experiences.end_date` |
| `bloodType` | `patient_profiles.blood_type` |

When the page is shown again, `App\Support\ProfileViewData` builds an array with the camelCase keys the Blade files already read through `data_get()`. The templates did not have to change.

`isApproved` in those arrays is the string `pending`, `approved`, or `cancelled`. The real column is `doctors.approval_status`. The profile page uses that string to decide which badge to show.

---

## 5. Build order

Do the steps in this order. Booking cannot work until registration, profiles, and time slots exist.

1. Point `TimeSlot` at the existing `time_slots` table.
2. Save every registration field and create the patient or doctor row.
3. Save doctor and patient profiles through the routes the forms already expect.
4. Add the `appointments` table, model, and booking rules.
5. Add the patient, doctor, receptionist, and public booking screens.
6. Replace the fake dashboard numbers with queries.
7. Write feature tests.

---

## 6. Step 1 — Time slots

### Why

The table `time_slots` already existed (`database/migrations/2026_10_11_090005_create_time_slots.php`). The PHP class was an empty `DoctorTimeSlots`. Eloquent would have looked for a table named `doctor_time_slots`, so saving hours would have failed.

A time slot is the doctor's weekly hours, not a booked visit. Example: "Monday 09:00 to 12:00, open". Many appointments can later fall inside that window, at different times, until each exact time is taken.

### What to add

`app/Models/TimeSlot.php`:

- `protected $table = 'time_slots'`
- Fillable: `doctor_id`, `day`, `starting_time`, `ending_time`, `is_available`
- Cast `is_available` to boolean
- `doctor()` belongsTo `Doctor`

On `app/Models/Doctor.php`:

```php
public function timeSlots(): HasMany
{
    return $this->hasMany(TimeSlot::class);
}
```

Delete the empty `DoctorTimeSlots` class so there is only one model for that table.

`day` is stored as a lowercase English weekday: `sunday`, `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`. The profile form's `<select>` already uses those values.

---

## 7. Step 2 — Registration

### Why

`resources/views/auth/register.blade.php` already posts `name`, `email`, `password`, `password_confirmation`, `phone`, `age`, `role`, `gender`, and `photo`. The old `RegisteredUserController` saved only name, email, and password. A new doctor would have had no `doctors` row, so they could not open a profile. A new patient would have had no `patient_profiles` row, so age would be lost.

### Files

- `app/Http/Requests/Auth/RegisterRequest.php` — validation
- `app/Http/Controllers/Auth/RegisteredUserController.php` — create the rows
- `app/Models/User.php` — fillable fields, slug helper, relationships
- `app/Support/ProfilePhoto.php` — store and delete the uploaded file

### Validation

`RegisterRequest` allows:

- `name`, `email` (unique), `password` confirmed
- `phone` required
- `age` integer from 0 to 120
- `role` only `patient` or `doctor`
- `gender` optional: `male`, `female`, or `other`
- `photo` optional image, max 2 MB

`receptionist` and `admin` are rejected here even if someone edits the HTML and posts them. Never trust the role that comes from a public form.

### What the controller writes

Inside `DB::transaction()`, so a failure does not leave a user without a profile:

1. `User::uniqueSlug($name)` turns "Dr Maya Chen" into `dr-maya-chen`. If that slug exists, it tries `dr-maya-chen-2`, then `-3`, and so on. The public doctor page is `/doctors/{slug}`.
2. If a photo was uploaded, `ProfilePhoto::store()` saves it on the `public` disk and returns a path like `storage/photos/abc.jpg`. The views already display that with `asset($photo)`, which needs `php artisan storage:link` once on the machine.
3. Create the user with name, slug, email, password, phone, photo, gender, role, and `is_active = true`. The `password` cast on `User` hashes the password.
4. Patient: `$user->patient_profile()->create(['age' => $age])`.
5. Doctor: `$user->doctor()->create(['approval_status' => 'pending'])`. Ticket price and specialty are still empty. The doctor fills those on the profile screen.
6. Fire the `Registered` event, log the user in, and `redirect()->route('dashboard')`.

`routes/web.php` already has `/dashboard`. It looks at `users.role` and redirects to `patient.dashboard`, `doctor.dashboard`, or `receptionist.dashboard`.

### Why a new doctor cannot be booked yet

`App\Http\Controllers\Public\DoctorController@index` lists doctors only when `approval_status` is `approved` and the related user `is_active` is true. Booking checks the same two facts and returns 403 otherwise. There is no admin approval screen in this pass. You approve a doctor by setting `doctors.approval_status` to `approved` in the database (or a later admin screen).

---

## 8. Step 3 — Profile save

### Why

The edit forms already do this:

```php
route('doctor.profile.update')   // PUT
route('patient.profile.update')   // PUT
```

Those route names did not exist, so the forms posted to a fallback URL that did nothing useful. The patient `show` and `edit` methods also returned view names that were not real files (`dashboard.patient.profile.show` and `patient.profile.edit`). The files that exist are:

- `resources/views/dashboard/patient/show.blade.php` → view name `dashboard.patient.show`
- `resources/views/dashboard/patient/edit.blade.php` → view name `dashboard.patient.edit`

### Files

- `app/Http/Controllers/Account/ProfileController.php`
- `app/Http/Requests/UpdateDoctorProfileRequest.php`
- `app/Http/Requests/UpdatePatientProfileRequest.php`
- `app/Support/ProfileViewData.php`
- `database/migrations/2026_10_11_151100_add_health_notes_to_patient_profiles_table.php`

Routes, inside the role groups in `routes/web.php`:

```text
GET  /doctor/profile         doctor.profile.show
GET  /doctor/profile/edit    doctor.profile.edit
PUT  /doctor/profile         doctor.profile.update

GET  /patient/profile        patient.profile.show
GET  /patient/profile/edit   patient.profile.edit
PUT  /patient/profile        patient.profile.update
```

Both roles share `Account\ProfileController`. `show` and `edit` look at `$request->user()->role` and return the matching view. `updateDoctor` and `updatePatient` are separate methods so each form has its own Form Request.

The route group already has `auth` and `role:doctor` or `role:patient`. The controller still checks the role and, for a doctor, aborts 404 when `$user->doctor` is missing. A doctor can update only their own row because the URL has no doctor id: it always loads `$request->user()->doctor`.

### Showing the profile

`ProfileViewData::doctor($user)` and `ProfileViewData::patient($user)` return plain arrays. The Blade files keep using `data_get($doctor, 'ticketPrice')` and `data_get($patient, 'bloodType')`.

Doctor array keys: `name`, `email`, `phone`, `gender`, `photo`, `bio`, `about`, `specialization`, `ticketPrice`, `isApproved`, `qualifications`, `experiences`, `timeSlots`.

Each qualification in that array uses `degree`, `university`, `startingDate`, `endingDate` even though the columns are `degree`, `institution`, `start_date`, `end_date`.

Each time slot uses `day`, `startingTime`, `endingTime`, `isAvailable`. Times are cut to `H:i` (`09:00`) because `<input type="time">` wants that shape.

Patient array keys: `name`, `email`, `phone`, `gender`, `photo`, `age`, `bloodType`, plus `bp`, `diabetic`, and `hyperthyroidism`. Those three health notes are stored as JSON in `patient_profiles.health_notes` because the form already had them and the original table only had `age` and `blood_type`.

### Saving a doctor

`UpdateDoctorProfileRequest`:

- Requires `name` and `ticketPrice` (`numeric`, minimum 0).
- Allows phone, gender, specialization, bio, about, and an optional new photo.
- Drops a qualification, experience, or time-slot row when every text field in it is blank. The form always renders one empty row so the Add button has a template. Saving that empty row would insert junk, and some columns such as `doctor_experiences.start_date` cannot be null.
- If a row has any value, the important fields become required (degree and university, position and hospital and start date, day and both times).
- Day must be one of the seven weekday names.
- `startingTime` must be before `endingTime`.
- An end date, when present, must be on or after the start date.

`ProfileController::updateDoctor` then, inside a transaction:

1. Updates the user: name, phone, gender, and photo if a new file was uploaded. Email is not on the form and is not changed. Slug is not changed, so the public URL stays stable.
2. Updates the doctor: specialization, ticket price, bio, about. It never writes `approval_status`, even if the request contains that field. `validated()` only returns fields listed in the Form Request rules, so a posted `approval_status` is ignored.
3. Deletes all qualifications for this doctor and inserts the posted ones.
4. Does the same for experiences. `position` is stored as `designation`. `hospital` is stored as `hospital_name`. If the end date is empty, `is_current` is true.
5. Does the same for time slots. Times are stored as `H:i:s` (`09:00:00`) so later comparisons are consistent.

Replacing the child rows matches the Add / Remove JavaScript already in the edit view. Whatever is still on the form is the new set. The JavaScript is in `@push('scripts')`. `layouts/dashboard.blade.php` needs `@stack('scripts')` before `</body>`, or those buttons never run.

After a successful save the controller redirects to `doctor.profile.show` with `session('status')` set to `Profile saved.`

### Saving a patient

`UpdatePatientProfileRequest` requires name, phone, and age. Blood type, gender, photo, and password are optional. Password uses Laravel's `confirmed` rule: leave both boxes empty to keep the current password. The health fields are `bp`, `diabetic`, and `hyperthyroidism`, each with `value` and `known`.

The controller updates the user, then `firstOrNew` on `patient_profile` so a patient who somehow has no profile row still gets one. Age goes to `age`, `bloodType` goes to `blood_type`, and the three health groups go into `health_notes`.

### Photo files

`ProfilePhoto::store()` writes the file first and returns the public path. The database update happens after that. If the transaction throws, the new file is deleted. If it commits and there was an old photo, the old file is deleted. The hidden `existing_photo` field in the form is not trusted. The previous path is read from the database so a user cannot point the photo column at an arbitrary path.

### `Doctor::getAttribute()`

Public components such as `resources/views/components/doctors/side-panel.blade.php` call `data_get($doctor, 'ticketPrice')` and then print `USD`. They also ask for `name`, `photo`, `averageRating`, and `totalRating`.

Eloquent's normal `getAttribute` only finds real columns (`ticket_price`) and relationship methods (`user`). `ticketPrice` is not a column. `name` lives on `users`, not `doctors`.

Overriding `getAttribute` answers those names:

- `ticketPrice` reads `ticket_price` and formats it as `150.00`. The Blade file adds the word USD, so this method does not include the currency word.
- `isApproved` reads `approval_status`.
- `averageRating` and `totalRating` read the snake_case rating columns.
- `name`, `email`, `phone`, `photo`, and `gender` are read from `$this->user`.

Every other key is passed to `parent::getAttribute($key)`, so `id`, `specialization`, `bio`, and `timeSlots()` still work.

The dashboard profile pages do not depend on this. They receive the array from `ProfileViewData`. The override exists so the public doctor page can keep passing the `Doctor` model into components that were already written.

---

## 9. Step 4 — The appointments table

### Why

Nothing in the old database represented a visit. Dashboards were looping over PHP arrays written by hand. A real visit needs one row that the patient, the doctor, and the receptionist can all read.

### Migration

`database/migrations/2026_10_11_151000_create_appointments_table.php` creates `appointments`:

| Column | Purpose |
| --- | --- |
| `patient_id` | `users.id`, cascade delete |
| `doctor_id` | `doctors.id`, cascade delete |
| `appointment_date` | date |
| `appointment_time` | time |
| `status` | `pending`, `confirmed`, `completed`, `cancelled` |
| `reason` | the "condition" text on the doctor table, nullable |
| `visit_type` | `check-up`, `consultation`, `follow-up`, `emergency`. Default `consultation`. This is the Type column on the receptionist table. |
| `notes` | nullable text. Not collected on these forms yet. |
| `cancelled_by` | user id, nullable, null on delete |
| `cancel_reason` | nullable string |
| timestamps | `created_at`, `updated_at` |

There is an index on doctor + date + time to make the "is this slot free?" query cheap.

A unique database index cannot say "unique only when status is not cancelled" in a way that is easy on both MySQL and the SQLite database used by tests. The uniqueness rule is enforced in PHP inside a transaction. See the next section.

### Model

`app/Models/Appointment.php`:

- `patient()` belongsTo `User` through `patient_id`
- `doctor()` belongsTo `Doctor`
- `cancelledBy()` belongsTo `User` through `cancelled_by`
- Cast `appointment_date` to a date
- `startsAt()` joins the date and time into one Carbon datetime in the app timezone
- `canPatientCancel()` is true only when status is `pending` or `confirmed` and `startsAt()` is still in the future
- `scopeUpcoming()` is "date is after today, or date is today and time is still now or later"
- `applyStatus()` is used by doctors and receptionists

On `User`:

```php
public function appointmentsAsPatient(): HasMany
{
    return $this->hasMany(Appointment::class, 'patient_id');
}
```

On `Doctor`:

```php
public function appointments(): HasMany
{
    return $this->hasMany(Appointment::class);
}
```

The foreign key must be `patient_id` on the patient relationship. Eloquent's default guess for `User::appointments()` would be `user_id`, which this table does not have.

---

## 10. Step 5 — When a time is bookable

All of this lives in `app/Services/AppointmentBooking.php`. The patient form and the receptionist form both call it. If the rule lived in two controllers, they would eventually disagree.

### The window versus the booked time

A time slot is a range. An appointment is one clock time inside that range.

Doctor hours: Monday 09:00–12:00, `is_available = true`.

Offered times, every 30 minutes: 09:00, 09:30, 10:00, 10:30, 11:00, 11:30.

09:00 is included. 12:00 is excluded. That rule is used everywhere: the dropdown, the server check, and the tests. `INTERVAL_MINUTES` is 30. `DAYS` is 14, meaning today plus the next 13 days.

### A time is offered only when all of these are true

1. The date is inside the next 14 days, including today.
2. `doctors.approval_status` is `approved`.
3. The doctor's `users.is_active` is true.
4. The weekday of the date matches `time_slots.day` for that doctor, and that row has `is_available` true. Sunday 10 October does not use a Monday row.
5. The time is one of the 30-minute steps from `starting_time` inclusive to `ending_time` exclusive.
6. That datetime is still in the future. `now()` uses the timezone in `config/app.php` (this project uses `UTC` unless `.env` changes it).
7. No appointment for that doctor, date, and time has a status other than `cancelled`.

`openTimes()` returns a list of `H:i` strings such as `10:00` for the dropdown. `slotsForNextDays()` calls it once per day and returns:

```text
[
  '2026-10-12' => ['09:00', '09:30', '10:00', ...],
  '2026-10-13' => [],
]
```

The book page prints that list into the HTML and uses a small script to refill the time `<select>` when the date changes. The script is only a convenience. The POST is checked again on the server.

### Creating the row

`AppointmentBooking::create()`:

1. Refuses the call unless the person being booked has `users.role = patient`. A receptionist cannot book themselves by posting their own user id.
2. Opens `DB::transaction()`.
3. Locks the doctor row with `lockForUpdate()`. The second request for the same doctor waits until the first transaction finishes.
4. Calls `assertOpen()`, which queries the database again (it does not trust the list that was rendered a minute ago).
5. Inserts the appointment with the time normalized to `H:i:s`.

If the time was taken between page load and submit, `assertOpen()` throws `ValidationException` with the message "That time is not available." on the `time` field. Laravel turns that into a redirect back to the form. The transaction rolls back, so the insert does not stick.

Patient bookings pass status `pending`. Receptionist bookings pass status `confirmed`. The controller chooses the status. It is not a field the patient can post.

Cancelling sets status to `cancelled`. The next `openTimes()` call ignores cancelled rows, so the clock time can be booked again.

### Status changes

`Appointment::applyStatus()` is for a doctor or a receptionist:

- If the current status is `completed` or `cancelled`, it refuses. Those two are final.
- The new status must be `confirmed`, `completed`, or `cancelled`.
- On cancel, it stores `cancelled_by` (the acting user) and the optional `cancel_reason`.

A patient does not use `applyStatus()`. Their cancel method checks `canPatientCancel()` first, so they cannot confirm, complete, or cancel a visit that already started.

---

## 11. Step 6 — Routes and who may open them

`routes/web.php` groups each role:

```php
Route::middleware(['auth', 'role:patient'])
    ->prefix('patient')
    ->name('patient.')
```

`prefix('patient')` makes the URL `/patient/...`. `name('patient.')` makes the route name `patient.bookings` when the route itself is named `bookings`.

`bootstrap/app.php` sends guests to `route('login')`. That is what makes "Book Appointment" while logged out end on the login page and then return.

| Route name | Method and URL | Who | Controller |
| --- | --- | --- | --- |
| `doctors.index` | GET `/doctors` | public | `Public\DoctorController@index` |
| `doctors.show` | GET `/doctors/{slug}` | public | `Public\DoctorController@show` |
| `doctors.book` | GET `/doctors/{doctor}/book` | patient | `Public\BookingController@create` |
| `doctors.book.store` | POST `/doctors/{doctor}/book` | patient | `Public\BookingController@store` |
| `dashboard` | GET `/dashboard` | auth | closure, redirects by role |
| `patient.dashboard` | GET `/patient/dashboard` | patient | `Patient\DashboardController@index` |
| `patient.bookings` | GET `/patient/bookings` | patient | `Patient\BookingController@index` |
| `patient.bookings.cancel` | POST `/patient/bookings/{appointment}/cancel` | patient | `Patient\BookingController@cancel` |
| `patient.profile.show` | GET `/patient/profile` | patient | `Account\ProfileController@show` |
| `patient.profile.edit` | GET `/patient/profile/edit` | patient | `Account\ProfileController@edit` |
| `patient.profile.update` | PUT `/patient/profile` | patient | `Account\ProfileController@updatePatient` |
| `doctor.dashboard` | GET `/doctor/dashboard` | doctor | `Doctor\DashboardController@index` |
| `doctor.appointments` | GET `/doctor/appointments` | doctor | `Doctor\AppointmentController@index` |
| `doctor.appointments.update-status` | POST `/doctor/appointments/{appointment}/status` | doctor | `Doctor\AppointmentController@updateStatus` |
| `doctor.profile.show` | GET `/doctor/profile` | doctor | `Account\ProfileController@show` |
| `doctor.profile.edit` | GET `/doctor/profile/edit` | doctor | `Account\ProfileController@edit` |
| `doctor.profile.update` | PUT `/doctor/profile` | doctor | `Account\ProfileController@updateDoctor` |
| `receptionist.dashboard` | GET `/receptionist/dashboard` | receptionist | `Receptionist\DashboardController@index` |
| `receptionist.appointments` | GET `/receptionist/appointments` | receptionist | `Receptionist\AppointmentController@index` |
| `receptionist.appointments.create` | GET `/receptionist/appointments/create` | receptionist | `Receptionist\AppointmentController@create` |
| `receptionist.appointments.store` | POST `/receptionist/appointments` | receptionist | `Receptionist\AppointmentController@store` |
| `receptionist.appointments.update-status` | POST `/receptionist/appointments/{appointment}/status` | receptionist | `Receptionist\AppointmentController@updateStatus` |

`{doctor}` is the doctor id, because the side panel calls `route('doctors.book', $doctorId)`. `{slug}` on the profile page is the user's slug. Those are different URLs (`/doctors/5/book` versus `/doctors/dr-maya-chen`), so they do not clash.

`/doctors` used to be registered twice: once as a static view and once as the controller. The static view won and the controller never ran, so the list had no `$doctors` variable. The static `Route::view('/doctors', ...)` line was removed. Search still uses the existing `query` input on the public list.

Check the names with:

```bash
php artisan route:list
```

### Ownership, after the role middleware

Role middleware is not enough. A doctor is allowed to open the status URL, but only for their own appointment.

- Patient cancel: `abort(403)` unless `appointment.patient_id` is the logged-in user.
- Doctor status: 404 if they have no doctor profile. 403 if `appointment.doctor_id` is not their doctor id.
- Receptionist status: any appointment. The role middleware already limited the route to receptionists.
- Public book: 404 if the doctor id does not exist (route model binding). 404 if the doctor row has no user. 403 if the doctor is not approved or the user is inactive.

---

## 12. Step 7 — Screens

### Patient dashboard

`app/Http/Controllers/Patient/DashboardController.php` passes two variables the existing view already understood: `$stats` and `$appointments`.

Stats:

- Upcoming: pending or confirmed, and still in the future (`upcoming()` scope)
- Completed: status `completed`
- Pending: status `pending`
- Doctors: distinct `doctor_id` among completed visits ("you have visited")

The appointment list is the latest 6 rows, newest date first. Each row is an array from `App\Support\AppointmentPresenter::patient()` with the keys the Blade table already prints: `doctor`, `specialization`, `date`, `time`, `status`. Status is the capital word `Pending`, because the CSS map in the view uses `Pending`, not `pending`.

The fake default arrays were removed from the Blade file. If there are no rows, the view's `@forelse` prints "You do not have any appointments yet." "Find a doctor" still links to `doctors.index`. A "View all" link goes to `patient.bookings`.

### My Bookings

View: `resources/views/dashboard/patient/bookings.blade.php`.

Same row layout as the patient dashboard, plus:

- A GET filter on `status`
- A cancel form on future pending or confirmed rows: `method="POST"`, `@csrf`, `@method('POST')`, optional `cancel_reason`
- Empty copy: "You do not have any appointments yet." or "No appointments match this filter."

Success flash: `Appointment cancelled.`

### Public booking page

View: `resources/views/public/doctors/book.blade.php`. It extends `layouts.public`, not the dashboard.

The side panel's Book button (`resources/views/components/doctors/side-panel.blade.php`) points at `route('doctors.book', $doctorId)` whenever that route exists. Doctors still do not see the button; the component already hides it when `role === 'doctor'`.

Guest flow:

1. Guest clicks Book Appointment.
2. The URL is `/doctors/5/book`, which has the `auth` middleware.
3. Laravel redirects to `/login` and remembers that URL as the intended URL.
4. `AuthenticatedSessionController` already ends with `redirect()->intended(route('dashboard'))`.
5. After a good login, the patient lands on the book page. If they had not been trying to book, intended is empty and they go to the dashboard as before.

The page shows the doctor name, specialization, and fee (`ticketPrice` plus `USD`), then a form: `date`, `time`, `reason`, `visit_type`.

`Public\BookingController@store` uses `StorePatientAppointmentRequest`, checks the doctor is approved, and calls `AppointmentBooking::create()` with status `pending`. Redirect: `patient.bookings` with `Appointment requested.`

### Doctor appointments

View: `resources/views/dashboard/doctor/appointments.blade.php`.

Columns match the doctor dashboard table — Patient, Condition (the `reason`), Time, Status — plus Date, plus action buttons.

Filters: `range` = `today` (default), `upcoming`, or `all`, and an optional status. Only this doctor's appointments are queried (`$request->user()->doctor->appointments()`).

Buttons post to `doctor.appointments.update-status` with a hidden `status` of `confirmed`, `completed`, or `cancelled`. Confirm is shown only while the visit is still pending. Final visits show no buttons.

The doctor dashboard (`Doctor\DashboardController`) passes today's rows into the existing table and four real stats: today's count, all pending, all confirmed, and completed this month. Payment and referral cards were removed because those tables do not exist. Inventing `$2,450` would be a lie. "Schedule appointment" links to `doctor.appointments`. "Send referral" and "View reports" stay as non-links.

### Receptionist

`config/dashboard.php` gained an Appointments link named `receptionist.appointments`. Existing links were not removed.

The board view is `resources/views/dashboard/receptionist/appointments.blade.php`. Columns match the receptionist dashboard: Patient, Doctor, Time, Type, Status, plus Date and actions. Default filter is today. Status actions are the same three buttons, posted to `receptionist.appointments.update-status`.

The create view is `resources/views/dashboard/receptionist/appointments/create.blade.php`.

- A GET search box `q` finds patients by name or email (maximum 20).
- A doctor `<select>` lists approved doctors whose user is active.
- Date, time, reason, and visit type use the same slot rules.
- The open times for every listed doctor for the next 14 days are embedded as JSON so changing the doctor or the date can refill the time dropdown without a calendar library.
- POST `receptionist.appointments.store` validates `doctor_id` (must exist and be approved) and `patient_id` (must be a user with role `patient`), then creates the visit with status `confirmed`.
- Redirects to the board with `Appointment booked.`

The receptionist dashboard shows today's real rows, "New patients" (patient users created today), and "Pending" (appointments still pending). The old fake task list and the fake John Smith rows were removed. Messages and follow-up cards were removed because there is no table for them. "Schedule appointment" links to the create form. Register patient, send message, and daily reports stay as non-links.

### How a dashboard row is shaped

`app/Support/AppointmentPresenter.php` turns one `Appointment` model into the array the old markup expected.

Doctor row: `patient`, `condition` (reason, or "Not specified"), `time` like `10:00 AM`, `date` like `12 Oct 2026`, `status` like `Pending`, plus `id`, `status_value`, and `can_update` for the buttons.

Patient row: `doctor`, `specialization`, `date`, `time`, `status`, plus `id` and `can_cancel`.

Receptionist row: `patient`, `doctor`, `time`, `date`, `type` (`Check-up`, `Consultation`, `Follow-up`, `Emergency`), `status`, plus the action fields.

The dashboard tables ignore the extra keys. The management pages use them.

---

## 13. Step 8 — Tests

`tests/TestCase.php` calls `$this->withoutVite()` so a missing compiled CSS file does not crash view tests.

`tests/Feature/Auth/RegistrationTest.php` posts the new required fields and checks:

- A patient gets a slug, a stored photo path, and a `patient_profiles.age`
- A doctor gets a `doctors` row with `approval_status = pending` and no patient profile
- Role `admin` is rejected and no user is created

`tests/Feature/ProfileCompletionTest.php` checks that a doctor can replace qualifications, experience, and a Monday time slot, that a bad time range does not wipe the saved qualification, that posting `approval_status=approved` does not approve them, and that a patient can save age, blood type, and health notes.

`tests/Feature/AppointmentBookingTest.php` checks the booking rules:

- A patient books 10:00 inside a 09:00–12:00 window, sees it on My Bookings and the patient dashboard, cancels it, and another patient can take that same clock time after the cancel
- A second patient cannot book a time that already has a non-cancelled row
- A patient cannot book when `is_available` is false
- A doctor posting `confirmed` on another doctor's appointment gets 403 and the status stays `pending`
- The owning doctor can confirm their own appointment
- A guest `GET` of the book page redirects to login, and the following login redirects back to that book page
- A receptionist cannot POST the patient booking URL, and can book through the receptionist form as `confirmed`
- Dashboards do not render the old sample name "John Smith"

Run:

```bash
php artisan test
```

The PHPUnit config uses SQLite in memory (`phpunit.xml`), so tests do not touch your MySQL database.

---

## 14. Worked example

Assume the app timezone is UTC and "now" is Sunday 11 October 2026, 09:00.

1. Maya registers with role `doctor`. She gets `users.role = doctor`, slug `dr-maya-chen`, and `doctors.approval_status = pending`. She is not on `/doctors`.
2. She opens Edit profile and saves specialization Surgeon, ticket price 150, and one slot: Monday, 09:00–12:00, open. Approval stays `pending`.
3. Someone sets her `approval_status` to `approved` in the database.
4. `/doctors` lists her. Her public profile side panel shows `150.00 USD` because `getAttribute('ticketPrice')` formatted `ticket_price` and the Blade file added `USD`.
5. Pat, a patient, clicks Book Appointment. The URL is `/doctors/{mayaDoctorId}/book`.
6. The next Monday inside 14 days is 12 October 2026. The dropdown offers 09:00 through 11:30.
7. Pat posts date `2026-10-12`, time `10:00`, visit type `consultation`, reason `Headache`.
8. `AppointmentBooking` locks Maya's doctor row, sees that Monday 10:00 is inside 09:00–12:00, sees no other active booking, and inserts status `pending`.
9. Pat is redirected to My Bookings and sees "Appointment requested."
10. Sam posts the same doctor, date, and time. The transaction sees Pat's row and redirects back with "That time is not available."
11. Maya opens Appointments, filters to upcoming, and posts status `confirmed`. Pat cannot confirm it himself. Another doctor posting that URL gets 403.
12. Pat cancels before 12 October 10:00. Status becomes `cancelled`, `cancelled_by` is Pat. Sam can now book 10:00.
13. A receptionist searches Pat by email, picks Maya, picks 11:00, and saves. That row is created as `confirmed` immediately.

---

## 15. What this pass deliberately does not do

- Referrals, payments, messages, insurance, reviews
- An admin screen to approve doctors
- Receptionist user management
- A reschedule form (the uniqueness rule is ready for one, because create and any future reschedule must use the same transaction)
- A calendar library
- Changes to the public home, about, contact, services, faq, or security pages
- Replacing the sidebar component, the stat partial, or the detail partial

---

## 16. File map

New or rewritten PHP:

```text
app/Models/TimeSlot.php
app/Models/Appointment.php
app/Models/User.php                          relationships, slug, fillable
app/Models/Doctor.php                        relationships and getAttribute
app/Services/AppointmentBooking.php          the slot rules
app/Support/ProfilePhoto.php
app/Support/ProfileViewData.php              camelCase arrays for the old forms
app/Support/AppointmentPresenter.php         camel-free display arrays for tables
app/Http/Requests/Auth/RegisterRequest.php
app/Http/Requests/UpdateDoctorProfileRequest.php
app/Http/Requests/UpdatePatientProfileRequest.php
app/Http/Requests/StorePatientAppointmentRequest.php
app/Http/Requests/StoreReceptionistAppointmentRequest.php
app/Http/Requests/UpdateAppointmentStatusRequest.php
app/Http/Requests/CancelAppointmentRequest.php
app/Http/Controllers/Auth/RegisteredUserController.php
app/Http/Controllers/Account/ProfileController.php
app/Http/Controllers/Public/DoctorController.php
app/Http/Controllers/Public/BookingController.php
app/Http/Controllers/Patient/DashboardController.php
app/Http/Controllers/Patient/BookingController.php
app/Http/Controllers/Doctor/DashboardController.php
app/Http/Controllers/Doctor/AppointmentController.php
app/Http/Controllers/Receptionist/DashboardController.php
app/Http/Controllers/Receptionist/AppointmentController.php
database/migrations/2026_10_11_151000_create_appointments_table.php
database/migrations/2026_10_11_151100_add_health_notes_to_patient_profiles_table.php
routes/web.php
tests/Feature/AppointmentBookingTest.php
tests/Feature/ProfileCompletionTest.php
tests/Feature/Auth/RegistrationTest.php
```

New Blade:

```text
resources/views/public/doctors/book.blade.php
resources/views/dashboard/patient/bookings.blade.php
resources/views/dashboard/doctor/appointments.blade.php
resources/views/dashboard/receptionist/appointments.blade.php
resources/views/dashboard/receptionist/appointments/create.blade.php
```

Touched so the old screens show real data: the three dashboard Blade files, the doctor partials (error lines), the register form (error lines), the side panel (book URL), `config/dashboard.php` (receptionist Appointments link), and `layouts/dashboard.blade.php` (`@stack('scripts')`).

---

## 17. If you rebuild it yourself

For each new screen, repeat this checklist.

1. Decide the route name first, especially if `config/dashboard.php` already mentions it.
2. Add the migration and the model relationships before writing the form.
3. Write the Form Request and confirm a bad post comes back with `@error` and `old()` values. Do not touch the database until this works.
4. Register the route with `auth`, then the role middleware.
5. In the controller, load the row and abort 403 unless this user owns it. Do this even though the middleware already checked the role.
6. If two screens share a rule, put the rule in one class. Booking is `AppointmentBooking`.
7. Redirect to a named route with `->with('status', '...')`.
8. Keep the existing CSS classes. Pass arrays whose keys match the `data_get()` calls already in the Blade file.
9. Add a feature test that acts as the right user and as the wrong user.

When `php artisan route:list` shows the names and `php artisan test` passes the booking cases, the feature is in place.
