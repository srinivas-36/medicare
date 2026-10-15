# 17 — Coverage and verification report

Audit date: 14 October 2026. Repository: `doctor-appointment`. No application PHP, Blade, migration, or environment file was edited. Documentation was added under `docs/application-handbook/`.

## Commands and outcomes

| Command | Outcome |
|---|---|
| `php artisan about --only=environment,drivers,cache` | Laravel 12.69.3, PHP 8.2.12, env local, debug on, MySQL, database session/cache/queue, log mail, views cached, config and routes not cached |
| `php artisan route:list --json` | 126 routes. Saved as `docs/application-handbook/route-list.json` |
| `php artisan migrate:status` | All 24 migrations **Ran**. Read-only. No migrate was executed |
| `php artisan test --no-ansi` | **89 passed, 5 failed**, 1066 assertions, 14.10s. Memory SQLite. Details in chapter 12 |
| `composer.lock` | `laravel/framework` v12.69.3 |
| `package-lock.json` | `tailwindcss` 3.4.19 |

The browser was not clicked through. Page behavior is from Blade, controllers, and tests. That is a limitation, not a claim that every pixel was seen.

## Inventory totals

| Item | Count | Where documented |
|---|---|---|
| Registered routes | 126 | Chapter 03 table, from Artisan |
| GET routes | 76 | Same table |
| Write routes (POST, PUT, PATCH, DELETE) | 50 | Same table |
| Eloquent models | 19 | Chapter 07 |
| Migration files | 24, all Ran | Chapter 07 |
| Form request classes | 19 | Chapter 06 |
| Custom middleware classes | 2 (1 unused) | Chapter 11 |
| Policies | 1 | Chapter 08 |
| Feature test files | 20 PHP files under `tests/Feature` (some paths duplicated in the Windows listing) | Chapter 12 |
| Failed tests | 5 | Chapter 12 |

Write endpoints in the route table are the write inventory. Chapter 06 covers their validation. A few writes validate inline in the controller rather than a form request. Those inline rules are listed in chapter 06.

## Routes

All 126 Artisan rows are in [03-complete-route-registry.md](03-complete-route-registry.md). None were dropped to save space.

Framework rows included on purpose: `/up`, `/api/user`, `/sanctum/csrf-cookie`, `/storage/{path}` GET and PUT.

Scoped bindings are not an Artisan column. They are documented from `routes/web.php` for three report routes.

## Pages

Chapter 04 includes every HTML route found: public pages, auth pages, patient, doctor, receptionist, admin, account, Breeze profile, and the practice pages that have no sidebar link. JSON and file routes are listed as non-pages.

`resources/views/errors/` does not exist. Error pages are framework defaults.

## Controllers

Chapter 05 names every controller file and the public actions found by searching `public function` under `app/Http/Controllers`. Private helpers are described where they change authorization (`involved`, `withAppointment`).

## Models and migrations

Chapter 07 names every model file and every migration file. Column lists are complete for `users` and the appointment status machine. Other tables are identified with their migration file and the constraints that were read (unique keys, cascades, the MySQL enum change). A line-by-line column catalog of every later `Schema::table` was not copied into the chapter when the migration file itself is the source. That is a documentation gap, not an unknown table.

## Forms

Chapter 06 tables the form requests and the inline `$request->validate` calls found by search. Availability exception, reschedule, referral decline and schedule, admin settings, receptionist profile, and the booking draft are included.

## Tests

Chapter 12 maps suites to workflows. It separates the executed run from proposed tests.

## Findings

Chapter 16 items F1–F4 cite a file or a failed test. F5–F17 cite a file. No finding depends on a guessed table or a guessed route.

## Secrets and patient data

`.env` was not copied. `.env.example` key names are mentioned without values. Logs and `storage/app/private` report files were not pasted. The test output contained a CSRF token from the in-memory run; it is not a production secret and is not repeated here.

## Application code

This audit did not modify controllers, views, migrations, or configuration. Generated handbook files and `route-list.json` are new. `_route-table.md` is a working extract of the same route list used to build chapter 03.

## Unresolved

| Question | What would answer it |
|---|---|
| Which `assertSee` string failed on the patient dashboard and the add-patient page | Re-run those two tests with a narrower assertion, or diff the HTML against the test strings |
| Whether `AppointmentBooking::create` turns a unique-index collision into a validation error | Read the catch block in that method and add a concurrent test |
| Exact column list for every altered table | Read each `Schema::table` closure. The files are named in chapter 07 |
| Pixel-level layout on a phone | Open the dashboard at a narrow width. Classes say the sidebar hides below `lg` |
| Whether `ClinicalAnalysis.php` is still called | Resolved: only `GenerateClinicalAnalysis` imports it. Controllers use `ClinicalAnalysisService` |

## Checklist

- [x] Stack verified from lockfiles and `artisan about`
- [x] All routes from `route:list` recorded
- [x] Migrations status recorded without migrating
- [x] Tests executed and failures named
- [x] Auth, ownership, and validation separated
- [x] Status transitions taken from `applyStatus`
- [x] Handbook files 00 through 17 written
- [ ] Browser click-through of each role
- [ ] Line-by-line column appendix for every migration
- [ ] Fixes for F1–F4 (out of scope for this documentation pass)

## How to read next

Start at [00-index-and-learning-roadmap.md](00-index-and-learning-roadmap.md). Then [01](01-system-architecture.md) and [02](02-request-lifecycle.md). Then the booking workflow in [09](09-business-workflows.md). Read [16](16-findings-and-recommendations.md) before treating deactivation or account deletion as safe.
