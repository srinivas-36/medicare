# 15 — File-by-file implementation index

Paths are relative to the repository root. “Active” means a route, a test, or another class calls it. “Unused” means nothing in `app/` or `routes/` calls it except its own file. “Uncertain” means only tests construct it.

Read a file only after the chapter that names it. This index is a map, not a second copy of the code.

## Bootstrap and routes

| Path | Responsibility | Next |
|---|---|---|
| `public/index.php` | Front controller | `bootstrap/app.php` |
| `bootstrap/app.php` | Routes, middleware alias, CSRF exception | `routes/web.php` |
| `bootstrap/providers.php` | Registers `AppServiceProvider` | Provider file |
| `routes/web.php` | Clinic routes | Controllers |
| `routes/auth.php` | Breeze auth routes | `app/Http/Controllers/Auth` |
| `routes/api.php` | `GET /api/user` | Sanctum |
| `routes/console.php` | Sample `inspire` command | Not a clinic workflow |
| `app/Providers/AppServiceProvider.php` | Empty | No gates |

## Middleware and policy

| Path | Status | Connects to |
|---|---|---|
| `app/Http/Middleware/EnsureUserHasRole.php` | Active | Alias `role` |
| `app/Http/Middleware/RoleMiddleware.php` | Unused no-op | Nothing |
| `app/Policies/AiAnalysisPolicy.php` | Active | Analysis form requests and `AiAnalysisController` |

## Controllers

All live under `app/Http/Controllers`. Chapter 05 lists every public method.

| Path | Routes |
|---|---|
| `Auth/AuthenticatedSessionController.php` | login, logout |
| `Auth/RegisteredUserController.php` | register |
| `Auth/PasswordResetLinkController.php` | forgot password |
| `Auth/NewPasswordController.php` | reset |
| `Auth/PasswordController.php` | PUT `/password` |
| `Auth/ConfirmablePasswordController.php` | confirm password |
| `Auth/EmailVerificationPromptController.php` | verification notice |
| `Auth/VerifyEmailController.php` | verification link |
| `Auth/EmailVerificationNotificationController.php` | resend |
| `Public/DoctorController.php` | doctor directory and profile |
| `Public/BookingController.php` | public book, draft, store, success |
| `Public/ReviewController.php` | reviews |
| `Public/StripeWebhookController.php` | webhook |
| `Patient/DashboardController.php` | patient home |
| `Patient/BookDoctorController.php` | find and book |
| `Patient/BookingController.php` | visits, reports, messages, cancel, reschedule |
| `Patient/HistoryController.php` | history |
| `Doctor/DashboardController.php` | doctor home |
| `Doctor/AppointmentController.php` | visits, status, consult, messages, reports |
| `Doctor/AiAnalysisController.php` | analysis |
| `Doctor/AvailabilityController.php` | hours and exceptions |
| `Doctor/PatientController.php` | roster |
| `Doctor/ReceptionistController.php` | staff |
| `Doctor/ReferralController.php` | referrals |
| `Doctor/PracticeController.php` | consultations, services, reports, reviews pages |
| `Receptionist/DashboardController.php` | desk home |
| `Receptionist/AppointmentController.php` | desk visits |
| `Receptionist/PatientController.php` | desk patients |
| `Admin/DashboardController.php` | admin |
| `Account/ProfileController.php` | role profiles |
| `Account/SecurityController.php` | password |
| `Account/NotificationController.php` | notifications |
| `ProfileController.php` | Breeze `/profile` |

## Form requests

`app/Http/Requests/` and `app/Http/Requests/Auth/`. Chapter 06 is the field table. Every file in that directory is active except where a route was not found. All 19 request classes found in the inventory are referenced by a controller.

## Models

`app/Models/`. Chapter 07. Nineteen files: `User`, `Doctor`, `DoctorQualification`, `DoctorExperience`, `PatientProfile`, `TimeSlot`, `Appointment`, `AppointmentReport`, `AppointmentMessage`, `AppointmentReschedule`, `Consultation`, `Referral`, `ReferralReport`, `Receptionist`, `DoctorReview`, `DoctorAvailabilityException`, `ClinicSetting`, `AiAnalysis`, `AiAnalysisAudit`.

## Services, support, jobs, notifications

| Path | Status | Used by |
|---|---|---|
| `app/Services/AppointmentBooking.php` | Active | Booking, slots, reschedule |
| `app/Services/AppointmentReports.php` | Active | File storage |
| `app/Services/ReferralSender.php` | Active | Real referrals |
| `app/Services/StripeCheckout.php` | Active | Payments |
| `app/Services/ClinicNotifier.php` | Active | Database notifications |
| `app/Services/PatientHistory.php` | Active | AI context |
| `app/Services/ClinicalAnalysis.php` | Only `GenerateClinicalAnalysis` | The HTTP analysis path uses `ClinicalAnalysisService` through `GenerateAiAnalysis` |
| `app/Services/AI/ClinicalAnalysisService.php` | Active from the job | Provider call |
| `app/Services/AI/ClinicalOutputValidator.php` | Active | Rejects bad model JSON |
| `app/Services/AI/MedicalReportExtractor.php` | Active | File text |
| `app/Services/AI/PatientContextBuilder.php` | Active | Prompt context |
| `app/Services/AI/InvalidAnalysisOutput.php` | Active | Validator failure type |
| `app/Jobs/GenerateAiAnalysis.php` | Active | `dispatchSync` |
| `app/Jobs/GenerateClinicalAnalysis.php` | Tests only | Not dispatched from controllers |
| `app/Notifications/ClinicNotification.php` | Active | `via` database |
| `app/Support/PublicCatalog.php` | Active | Home and services |
| `app/Support/MedicalFile.php` | Active | Private disk |
| `app/Support/ProfilePhoto.php` | Active | Avatars |
| `app/Support/AppointmentPresenter.php` | Active | Visit display |
| `app/Support/AppointmentPage.php` | Active | Shared list/detail data |
| `app/Support/ClinicSchedule.php` | Active | `Doctor\PatientController` and `Receptionist\AppointmentController` |
| `app/Support/PatientRoster.php` | Active | Roster pages |
| `app/Support/DoctorReviews.php` | Active | Public profile |
| `app/Support/MockReferralDirectory.php` | Active | Preview ids, not the `doctors` table |
| `app/Support/MockReferralInbox.php` | Active | Sample referral inbox |
| `app/Support/ReferralDesk.php` | Active | Referral UI data. Calls `ReferralSuggestions` |
| `app/Support/ReferralSuggestions.php` | Active | `ReferralDesk`, `Doctor\PatientController`, mock directory |
| `app/Support/ChartAnalysis.php` | Active | `AiAnalysisController` builds a local chart summary |
| `app/Support/AnalysisBrief.php` | Active | Analysis show view and doctor visit partial |
| `app/Support/ProfileViewData.php` | Active | Account profile and availability screens |

## Views

| Directory | Pages |
|---|---|
| `resources/views/public/` | Home, about, contact, services, faq, security, doctors, book, checkout |
| `resources/views/auth/` | Login, register, forgot, reset, confirm, verify |
| `resources/views/layouts/` | `public`, `dashboard`, `app`, `guest`, `navigation` |
| `resources/views/dashboard/patient.blade.php` | Patient home |
| `resources/views/dashboard/doctor.blade.php` | Doctor home |
| `resources/views/dashboard/receptionist.blade.php` | Desk home |
| `resources/views/dashboard/appointments/` | Shared visit list and detail |
| `resources/views/dashboard/doctor/` | Availability, patients, referrals, analysis, practice, profile, receptionists |
| `resources/views/dashboard/receptionist/` | Desk appointments and patients |
| `resources/views/dashboard/admin/` | Admin screens |
| `resources/views/account/` | Security and notifications |
| `resources/views/profile/` | Breeze profile |
| `resources/views/components/` | Icons, home sections, dashboard widgets |
| `resources/views/errors/` | Absent |

Chapter 04 names the view for each URL.

## Frontend

| Path | Role |
|---|---|
| `vite.config.js` | Two inputs: CSS and `app.js` |
| `tailwind.config.js` | Tailwind 3 theme |
| `resources/css/app.css` | Tailwind entry |
| `resources/js/app.js` | Alpine and submit lock |
| `resources/js/bootstrap.js` | Axios |
| `resources/js/confirm-dialog.js` | Confirm UI |
| `resources/js/analysis-poll.js` | Analysis status |
| `resources/js/doctor-reviews.js` | Reviews |
| `resources/js/referral-preview.js` | Preview |
| `resources/js/visit-chat.js` | Messages |
| `package.json` | Tailwind 3.4, Vite 7, Alpine 3 |
| `config/dashboard.php` | Sidebar links |

## Database

Every file in `database/migrations/` is listed in chapter 07 and has status Ran. `database/factories/UserFactory.php` and `database/seeders/DatabaseSeeder.php` are the only factory and seeder.

## Tests

Every file under `tests/Feature` and `tests/Unit` is listed in chapter 12. `tests/TestCase.php` is the base. `phpunit.xml` forces SQLite memory.

## Config

| Path | Note |
|---|---|
| `config/auth.php` | Session guard, password broker |
| `config/database.php` | Default connection from the environment. Live app reported MySQL |
| `config/session.php` | Driver from the environment |
| `config/filesystems.php` | `local` disk is private and `serve` is true |
| `config/services.php` | Stripe and AI keys via `env`. Do not copy values |
| `config/queue.php`, `cache.php`, `mail.php`, `logging.php` | Database queue and cache, log mail |
| `.env.example` | Sample keys. SQLite in the sample, MySQL on this machine |

## Do not treat as application source

`vendor/`, `storage/framework/views/` (compiled Blade), `storage/logs/`, `bootstrap/cache/`, and `public/hot` are generated or third-party. `storage/framework/testing/` holds test files. Do not document patient file contents from `storage/app/private`.

## Related

[03-complete-route-registry.md](03-complete-route-registry.md), [05-controllers-and-business-logic.md](05-controllers-and-business-logic.md).
