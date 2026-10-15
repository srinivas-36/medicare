# Medicare application handbook

This handbook explains the Medicare doctor-appointment application as it exists in this repository. It is written for someone who has not used Laravel before. Every claim about behavior is tied to a file, a command, or a test that was actually run.

Older notes in `docs/` (`appointment-implementation-guide.md`, `medicare-implementation-plan.md`, `dashboard-ux-audit.md`, `dashboard-redesign-checklist.md`) were left in place. This handbook does not replace them. When they disagree with the code, the code wins.

## What Medicare does

Medicare is a server-rendered clinic system. A person can browse approved doctors, create a patient or doctor account, and book a visit. Doctors manage a schedule, see their own patients, write a consultation note, exchange messages, upload and open reports, send referrals, and optionally ask an external model to draft a clinical summary. Receptionists book and update visits for the one doctor they are assigned to. Administrators approve or reject doctor applications, activate or deactivate accounts, and save clinic-wide booking rules.

Payments can go through Stripe when a visit has a price and the clinic payment policy requires it. A price of zero is confirmed without Stripe. That behavior is implemented in `app/Services/AppointmentBooking.php` and `app/Http/Controllers/Public/StripeWebhookController.php`, and it is covered by `tests/Feature/AppointmentBookingTest.php`.

## Actual technology stack

Verified on 14 October 2026 with `php artisan about`, `composer.lock`, and `package-lock.json`.

| Piece | What it is, in plain language | Verified version / setting |
|---|---|---|
| PHP | The language the server runs | 8.2.12 |
| Laravel | The framework that receives web requests and talks to the database | 12.69.3 (`composer.lock`: `v12.69.3`) |
| Laravel Breeze | The starter that supplied login, registration, and password screens | `laravel/breeze` ^2.4 in `composer.json` (development dependency) |
| Laravel Sanctum | Token/cookie helper for the small `/api/user` route | ^4.0 |
| Blade | HTML templates rendered on the server | Part of Laravel |
| Vite | Tool that bundles CSS and JavaScript | 7.x in `package.json` (`vite` ^7.0.7) |
| Tailwind CSS | Utility CSS. Classes such as `bg-white` become real CSS | **3.4.19** installed (`package-lock.json`). Config: `tailwind.config.js`. Plugin: `@tailwindcss/forms` 0.5.x |
| Alpine.js | Small JavaScript library for menus, tabs, and chat widgets | ^3.4.2 |
| MySQL | The database this local app is configured to use | `php artisan about` reports `Database: mysql` |
| Sessions, cache, queues | Stored in database tables, not in files or Redis | `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION` are `database` in the running app |
| Mail | Written to the log, not sent to a real inbox | `MAIL_MAILER=log` in `.env.example`; `about` reports `log` |
| Stripe | Card checkout | `stripe/stripe-php` ^22. Keys come from `STRIPE_SECRET_KEY` and `STRIPE_WEBHOOK_SECRET`. Values are not copied here |
| Google Gemini-style HTTP API | Optional clinical-summary generator | `config/services.php` key `ai`. Empty key skips the call |

`.env.example` still shows SQLite as the sample database. The running application reported MySQL. The example file and the live environment are different. Do not assume SQLite is what your machine is using.

`php artisan migrate:status` showed every migration in `database/migrations/` as **Ran** on that MySQL database. The active schema matches the migration files for this machine. Tests do not use that database. `phpunit.xml` forces SQLite in memory.

## Documentation map

Read in this order if you are new. Jump by question if you are debugging.

| File | Question it answers |
|---|---|
| [00-index-and-learning-roadmap.md](00-index-and-learning-roadmap.md) | Where to start |
| [01-system-architecture.md](01-system-architecture.md) | What the pieces are and how a request enters Laravel |
| [02-request-lifecycle.md](02-request-lifecycle.md) | What happens from the browser click to the HTML |
| [03-complete-route-registry.md](03-complete-route-registry.md) | Every URL the application registers |
| [04-page-rendering-handbook.md](04-page-rendering-handbook.md) | Every screen, what draws it, and what each control does |
| [05-controllers-and-business-logic.md](05-controllers-and-business-logic.md) | Every controller method |
| [06-validation-and-input-processing.md](06-validation-and-input-processing.md) | Every write form and its rules |
| [07-database-and-eloquent.md](07-database-and-eloquent.md) | Tables, models, and relationships |
| [08-authentication-authorization-security.md](08-authentication-authorization-security.md) | Login, roles, ownership, and risks |
| [09-business-workflows.md](09-business-workflows.md) | Booking, status changes, referrals, reports, payments |
| [10-blade-frontend-and-assets.md](10-blade-frontend-and-assets.md) | Templates, Tailwind, Vite, Alpine |
| [11-middleware-errors-and-responses.md](11-middleware-errors-and-responses.md) | Middleware order, 403/404/419/422/500 |
| [12-testing-and-quality-assurance.md](12-testing-and-quality-assurance.md) | What the tests cover, and the run from this audit |
| [13-debugging-playbook.md](13-debugging-playbook.md) | How to trace a broken page |
| [14-glossary-and-concept-reference.md](14-glossary-and-concept-reference.md) | Terms, with a mark for used / absent |
| [15-file-by-file-implementation-index.md](15-file-by-file-implementation-index.md) | Where to open the code |
| [16-findings-and-recommendations.md](16-findings-and-recommendations.md) | Defects and risks. No code was changed |
| [17-coverage-and-verification-report.md](17-coverage-and-verification-report.md) | What was checked, and what was not |

## Prerequisites

You can read this handbook with no Laravel background. To run the app yourself you need:

- PHP 8.2 or newer, with the extensions Laravel needs (this machine has 8.2.12).
- Composer, to install PHP libraries.
- Node.js and npm, to compile CSS and JavaScript.
- A database. This checkout is using MySQL. The sample `.env.example` shows SQLite.

You do not need to memorize the framework before chapter 01. Each chapter defines a term the first time it matters.

## Recommended reading order

1. Chapter 00 (this file) and the glossary entries for HTTP, route, controller, Blade, and Eloquent in [chapter 14](14-glossary-and-concept-reference.md).
2. [Chapter 01](01-system-architecture.md) so the folders make sense.
3. [Chapter 02](02-request-lifecycle.md) once, using the home page as the example.
4. Pick one workflow in [chapter 09](09-business-workflows.md): patient booking is the best first workflow.
5. Follow that workflow into [chapter 03](03-complete-route-registry.md), [chapter 04](04-page-rendering-handbook.md), [chapter 05](05-controllers-and-business-logic.md), and [chapter 06](06-validation-and-input-processing.md).
6. Read [chapter 07](07-database-and-eloquent.md) when a query confuses you, and [chapter 08](08-authentication-authorization-security.md) before you change who can see a record.
7. Use [chapter 13](13-debugging-playbook.md) when something breaks.
8. Read [chapter 16](16-findings-and-recommendations.md) before you treat the app as finished.

## Beginner-to-advanced roadmap

**Beginner.** Open `routes/web.php`. Find `Route::get('/', ...)`. That is the home page. The closure calls `view('public.home', ...)`. Open `resources/views/public/home.blade.php`. It extends `layouts.public`. That is the whole idea of this application: a URL is matched to a small piece of PHP, which loads data and returns a template.

**Intermediate.** Follow a POST. The patient booking form posts to `doctors.book.store`. Middleware requires a logged-in patient. `StorePatientAppointmentRequest` checks the fields. `AppointmentBooking` decides whether the time is free. Eloquent inserts a row. The browser is redirected.

**Advanced.** Separate three questions that the code sometimes mixes together:

- Is the input well formed? (validation)
- Is this user allowed to touch this record? (authorization)
- Is the clinic in a state that allows the change? (business rule, plus database constraints such as the unique `active_slot`)

Chapter 08 shows places where those three layers are not equally strong. The most important gap found in this audit: `users.is_active` is stored and edited by an administrator, but login does not read that flag.

## How to navigate from a page to its code

1. Look at the browser address. Example: `/patient/bookings/15`.
2. Search `routes/web.php` for `bookings/{appointment}`. The name is `patient.bookings.show`. The action is `Patient\BookingController@show`.
3. Or run `php artisan route:list --name=patient.bookings.show`.
4. Open the controller method. Note `abort_unless` checks. Those are ownership checks.
5. Note `return view('dashboard.appointments.show', ...)`. Open that Blade file.
6. If the method uses `Appointment::` or `$appointment->reports()`, open `app/Models/Appointment.php` and the matching migration under `database/migrations/`.
7. Search `tests/` for the route name to see the behavior the project already tries to lock in.

## How to tell verified behavior from a guess

| Label used in this handbook | Meaning |
|---|---|
| Verified | Read in source, or produced by a command cited in [chapter 17](17-coverage-and-verification-report.md) |
| Partially implemented | The route or screen exists, but a link, rule, or side effect is missing or inconsistent |
| Referenced but missing | A name is mentioned and the file or method is not there. None of the main route targets were missing |
| Inferred | A reasonable reading that was not executed in a browser during this audit |
| Not applicable | The concept is not used by this repository |

A route existing does not prove the screen matches the tests. This audit ran `php artisan test`. Result: **89 passed, 5 failed**, 1066 assertions, 14.10 seconds. The failures are listed in [chapter 12](12-testing-and-quality-assurance.md). The browser was not driven click-by-click in this pass; UI behavior is taken from Blade, JavaScript, and those tests.

## Short glossary

Longer entries are in [chapter 14](14-glossary-and-concept-reference.md).

| Term | One-sentence meaning in this app |
|---|---|
| Route | A URL plus an HTTP method, registered in `routes/web.php` or `routes/auth.php` |
| Middleware | Code that runs before the controller. `auth` means "must be logged in". `role:patient` means the user's `role` column must be `patient` |
| Controller | A PHP class method that handles one action, such as showing a booking |
| Eloquent model | A PHP class that represents a database table. `Appointment` represents `appointments` |
| Migration | A PHP file that creates or changes tables. All 24 application migrations have been run on the local MySQL database |
| Blade | The template language. `{{ $name }}` prints text with HTML characters escaped |
| Form request | A class such as `StorePatientAppointmentRequest` that validates a form before the controller method runs |
| Session | Server-side data tied to a cookie. Login state lives here. The driver is the `sessions` table |
| CSRF | A hidden token on POST forms so another website cannot submit a form as you |

## Roles that actually exist

The `users.role` column is an enum: `patient`, `doctor`, `receptionist`, `admin`. Default is `patient`. Registration in `RegisterRequest` allows only `patient` and `doctor`. Receptionist accounts are created by a doctor. Admin accounts are not created by the public registration form.

A doctor also has `doctors.approval_status`: `pending`, `approved`, or `cancelled`. Public booking requires `approved`. Rejection stores `cancelled`, not a separate `rejected` value (`Admin\DashboardController::reject`).
