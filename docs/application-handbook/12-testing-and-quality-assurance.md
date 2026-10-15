# 12 — Testing and quality assurance

## How this project tests

`phpunit.xml` sets `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`. Tests do not touch the MySQL database used by `php artisan serve`.

`tests/TestCase.php` calls `withoutVite()` so pages render without a built asset manifest, and sets `services.ai.key` to an empty string.

There is no browser driver (no Dusk, no Playwright) in `composer.json`. Tests are HTTP feature tests: `get`, `post`, `actingAs`, `assertOk`, `assertRedirect`, `assertSee`, `assertForbidden`.

`tests/Unit/ExampleTest.php` only asserts true. It does not cover Medicare.

## Command actually run

```
php artisan test --no-ansi
```

Date: 14 October 2026. Duration: 14.10s.

**Result: 89 passed, 5 failed, 1066 assertions.** Exit code 1.

The five failures:

| Test | What the output showed |
|---|---|
| `Tests\Feature\AppointmentConversationTest::test_patient_and_doctor_can_exchange_messages_on_the_visit` | The visit page HTML did not satisfy a later `assertSee`. The test looks for the two message bodies, `Type a message`, and `Chat with` |
| `Tests\Feature\DashboardWorkspaceTest::test_doctor_can_add_a_new_patient_and_an_existing_one` | Response title was “Add a patient & Book Appointment”. An expected string on that screen was missing |
| `Tests\Feature\DashboardWorkspaceTest::test_patient_books_a_doctor_from_the_dashboard` | Patient dashboard HTML did not contain every sentence the test expects (`Welcome back`, `Find a Doctor`, and the other `assertSee` calls in that method) |
| `Tests\Feature\DoctorAnalysisWorkflowTest::test_doctor_review_and_approval_are_audited_and_do_not_change_the_visit_note` | Referral show page contained `Unapproved model summary.` The test forbids that string |
| `Tests\Feature\ProfileCompletionTest::test_doctor_saves_profile_details_without_changing_approval` | Validation error “The phone field format is invalid.” Redirect went to `http://localhost` instead of `doctor.profile.show`. The posted phone was `5551234` |

These are executed results, not proposals. Other methods in those files passed.

## Feature files and what they lock in

| File | Behaviors |
|---|---|
| `AppointmentBookingTest` | Book, cancel, reports, taken slots, other doctor forbidden, guest login, draft restore, next-month booking, webhook, free visit, receptionist books and cannot use the patient form |
| `AppointmentConversationTest` | Upload without analysis, messages (one failure), no analysis on confirm, clinical brief, delete report, recommendation list |
| `AppointmentPagesTest` | Filters, paging, status tabs, empty copy, saved notes |
| `Auth/*` | Login, logout, registration, verification, password reset, confirmation, password update |
| `ConsultationReferralTest` | History isolation, other doctor 403, consult note, analysis job, referral snapshot, selected reports, accept/decline once, schedule, self-referral validation, sample specialists |
| `DashboardWorkspaceTest` | Add patient (failed), doctor home, patient book page (failed), 12 doctors per page |
| `DoctorAnalysisWorkflowTest` | No provider on upload, unauthorized analysis, selected reports, single open job, malformed output, review/approve (failed) |
| `DoctorReviewTest` | Publish and update, guests forbidden, ten per page |
| `ExampleTest` | Home returns success |
| `MedicareWorkflowTest` | Horizon and leave, private history, admin approve, password change |
| `ProfileCompletionTest` | Doctor profile (failed), patient profile, weekly hours |
| `ProfileTest` | Breeze profile update and delete |
| `PublicServicesTest` | Three services on the home page, six on `/services` |
| `ReceptionistDeskTest` | Only the doctor manages receptionists, desk booking, search, notification, “who added the patient” |
| `SlotAvailabilityTest` | Saved slots, leave overlap, forged slots, reschedule releases the slot |

## Map of important routes to tests

Not every route has a dedicated test. These are the ones the suite actually exercises by route name or HTTP call:

| Area | Covered by |
|---|---|
| Home, services | `ExampleTest`, `PublicServicesTest` |
| Register, login, logout, passwords, verification | `tests/Feature/Auth/*` |
| Public and patient booking, webhook | `AppointmentBookingTest`, `SlotAvailabilityTest`, `MedicareWorkflowTest` |
| Patient history privacy | `MedicareWorkflowTest` |
| Doctor visits and status | `AppointmentPagesTest`, `AppointmentBookingTest` (cross-doctor) |
| Messages and reports | `AppointmentConversationTest` |
| Analysis and referrals | `DoctorAnalysisWorkflowTest`, `ConsultationReferralTest` |
| Receptionist desk | `ReceptionistDeskTest` |
| Admin approve | `MedicareWorkflowTest` |
| Profiles | `ProfileTest`, `ProfileCompletionTest` |
| Reviews | `DoctorReviewTest` |
| Practice pages | `DashboardWorkspaceTest` GETs `doctor.consultations`, `services`, `reports`, `reviews` |

## Routes with weak or no direct test

From the route list, these are not the subject of a named test that this audit read end to end:

- `GET /about`, `/contact`, `/faq`, `/security` (static views)
- `GET /api/user`
- `GET /sanctum/csrf-cookie`
- `storage.local` signature behavior
- Admin user deactivation and “last admin” guard
- Admin settings update
- `account.notifications.read` redirect
- `is_active` blocking login (there is no test because the check does not exist)
- Email verification is tested, but no test requires verification before booking

## Proposed tests (not written)

Do not treat these as existing.

1. Inactive user cannot log in and an existing session is rejected after deactivation.
2. Patient A cannot GET, POST, or DELETE patient B’s report, message, or cancel URL.
3. Receptionist cannot open another doctor’s appointment id.
4. Doctor cannot set status `completed` twice.
5. Two parallel bookings of one slot: one succeeds, one fails, and the error is a validation message rather than a 500.
6. Referral recipient does not see an unapproved model string (this is the failing test; keep it).
7. Admin cannot deactivate the last admin (the code exists; a test would lock it).
8. `PUT doctor.profile.update` with a 10-digit phone redirects to the profile and ignores `approval_status`.
9. Stripe webhook with the wrong amount does not confirm.
10. File upload with a disallowed extension is rejected and nothing is stored.

## How to run tests safely

```
php artisan test
```

or one file:

```
php artisan test tests/Feature/AppointmentBookingTest.php
```

This uses memory SQLite even if `.env` points at MySQL. Do not point `DB_DATABASE` in `phpunit.xml` at the clinic database. Do not run `migrate:fresh` against the MySQL database to “fix” a test.

A failure’s first line names the test. `assertSee` prints the whole HTML. Search the test method for `assertSee` and compare those strings to the Blade file.

## Related

[16-findings-and-recommendations.md](16-findings-and-recommendations.md)
