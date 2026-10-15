# 14 — Glossary and concept reference

Each entry says whether Medicare **uses** it, it is **absent**, or it is **not applicable**. “Uses” means the repository or the installed Laravel 12 pipeline actually relies on it.

## PHP

| Concept | Status | In this app |
|---|---|---|
| Variables, arrays, functions | Uses | Every controller |
| Classes, objects, methods, constructors | Uses | Controllers and services. Example: `StripeWebhookController` constructor receives `StripeCheckout` and `AppointmentBooking` |
| Namespaces and `use` | Uses | `App\Models\Appointment` |
| Visibility | Uses | Public actions, private helpers such as `ReferralController::involved` |
| Inheritance | Uses | Controllers extend `Controller`. `User` extends `Authenticatable` |
| Interfaces | Uses | Framework contracts. The app does not define its own clinic interface |
| Traits | Uses | `Notifiable`, `HasFactory`, `HasApiTokens` on `User` |
| Type declarations | Uses | `index(Request $request): View` |
| Exceptions | Uses | `ValidationException`, `abort()` |
| Static methods | Uses | `User::uniqueSlug`, `ClinicSetting::current()` |
| Closures | Uses | Home route, validation callbacks in `StoreReferralRequest` |
| Arrow functions | Uses | `fn () => route('login')` in `bootstrap/app.php` |
| Dependency injection | Uses | Method and constructor parameters |

**Analogy for a class:** a form with labeled blanks (properties) and actions (methods). `Appointment` is the form for one visit row.

**Mistake:** calling a relationship method and expecting a single model without executing the query. `$appointment->patient()` returns the relationship object. `$appointment->patient` returns the user.

## Laravel

| Concept | Status | Where |
|---|---|---|
| Service container | Uses | Builds controllers and services |
| Service providers | Uses | Only `AppServiceProvider`, and it is empty |
| Facades | Uses | `Auth`, `Route`, `DB`, `Storage`, `Hash` |
| Routing | Uses | `routes/web.php`, `routes/auth.php`, `routes/api.php` |
| Controllers | Uses | `app/Http/Controllers` |
| Middleware | Uses | Chapter 11 |
| Form requests | Uses | `app/Http/Requests` |
| Validation | Uses | Chapter 06 |
| Guards | Uses | `web` session guard. Sanctum on `/api/user` |
| Policies | Uses | `AiAnalysisPolicy` only, called manually |
| Gates | Absent | `AppServiceProvider::boot` is empty. `can` middleware is unused |
| Eloquent | Uses | `app/Models` |
| Query builder | Uses | `DB::table` inside migrations and some aggregates |
| Migrations | Uses | 24 files, all Ran locally |
| Seeders | Uses | `DatabaseSeeder` exists. Not run during this audit |
| Factories | Uses | `UserFactory` only |
| Blade | Uses | `resources/views` |
| Sessions | Uses | Database driver |
| CSRF | Uses | Except Stripe webhook |
| Events | Partial | Framework `Registered` and `Lockout`. No `app/Events` |
| Listeners | Absent | No `app/Listeners` |
| Queues | Partial | `jobs` table and `queue:listen` in `composer dev`. Analysis uses `dispatchSync`, so the worker is not required for that path |
| Jobs | Uses | `GenerateAiAnalysis` (live), `GenerateClinicalAnalysis` (tests and class only) |
| Notifications | Uses | `ClinicNotification`, database channel |
| Mail | Partial | Password reset through the log mailer. No `app/Mail` |
| Transactions | Uses | Registration, new patient, Stripe webhook |
| Pagination | Uses | Doctor lists, admin, notifications |
| Rate limiting | Uses | Login and several `throttle` routes |
| Caching | Uses | Database cache store. Config and routes are not cached. Views are cached |
| Soft deletes | Absent | No `SoftDeletes` on the models that were read |
| API resources | Absent | JSON is built in controllers |
| Resource routes | Absent | Routes are explicit |

**When not to add a policy:** a one-line check that is clearer beside the query. **When to add one:** the same ownership rule is copied into ten methods, which is the appointment situation today.

## Web

| Concept | Status | Note |
|---|---|---|
| HTTP, URL, headers, cookies | Uses | Chapter 02 |
| GET, POST, PUT, PATCH, DELETE | Uses | All five appear in the route list |
| Status codes | Uses | 200, 302, 400, 403, 404, 419, 422, 429, 500 |
| Redirects | Uses | After writes |
| JSON | Uses | Search, messages, analysis, reviews |
| HTML forms | Uses | Blade |
| CSRF | Uses | |
| Server-side rendering | Uses | Blade |
| Client-side rendering | Partial | Alpine widgets, not a SPA |
| DNS, HTTPS | Not in the repo | The local URL is `http://localhost` |

## Database

| Concept | Status |
|---|---|
| Tables, keys, foreign keys, indexes | Uses. Chapter 07 |
| Unique constraints | Uses. Email, `active_slot`, review pair, doctor-patient pair |
| Transactions | Uses |
| Row lock | Uses. `lockForUpdate` on the Stripe update |
| Enums (MySQL) | Uses for role, gender, approval, appointment status |
| Nullability | Uses. Many clinical text columns are nullable |

## Security

| Concept | Status |
|---|---|
| Authentication | Uses. Session login |
| Authorization | Uses. Roles and per-row checks. Not a full policy map |
| Ownership | Uses. `patient_id` and `doctor_id` |
| CSRF | Uses |
| XSS | Mitigated by Blade escaping where `{{ }}` is used |
| SQL injection | Mitigated by the query builder |
| IDOR | Defended on visit routes that were read. New nested routes need the same checks |
| Password hashing | Uses. `hashed` cast and `Hash::make` where already hashed values are left alone |
| Encryption of session payload | Off in `.env.example` (`SESSION_ENCRYPT=false`) |
| Mass assignment | `$fillable` lists. `role` is fillable. See chapter 08 |

## Clinic words

| Word | Meaning here |
|---|---|
| Approval | `doctors.approval_status`, not `users.role` |
| Slot | A start time on a date for one doctor. Stored as `time_slots` for the week and `active_slot` when booked |
| Visit | An `appointments` row |
| Report | A file row in `appointment_reports` |
| Referral | A row in `referrals`, or a mock preview that is not a row |
| Desk | The receptionist screens |

## Related

[00-index-and-learning-roadmap.md](00-index-and-learning-roadmap.md)
