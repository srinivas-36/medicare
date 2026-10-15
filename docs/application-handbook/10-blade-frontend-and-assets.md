# 10 — Blade, frontend, and assets

## Blade in this project

Blade is a template language that compiles to PHP in `storage/framework/views`. `php artisan about` reported views **cached**. After you edit a template, a cached view can still be old until the cache is cleared (`php artisan view:clear`). This audit did not clear it.

| Syntax | Meaning | Risk |
|---|---|---|
| `{{ $name }}` | Print escaped text | Safe for names and notes |
| `{!! $html !!}` | Print raw HTML | XSS if the value is user input |
| `@extends('layouts.dashboard')` | Use a parent layout | Child fills `@section` |
| `@section('title')` / `@yield('title')` | Fill a hole in the layout | |
| `@include('partials.footer')` | Insert another template | Shares the current variables |
| `<x-ui.icon name="menu" />` | Component `resources/views/components/ui/icon.blade.php` | |
| `@csrf` | Hidden CSRF token | Required on POST forms |
| `@method('PUT')` | Hidden `_method` | So a POST is handled as PUT |
| `@error('email')` | Validation message | Works after a failed POST |
| `old('email')` | Previous input | Empty on the first visit |
| `@if` `@foreach` `@forelse` | Conditions and loops | `@forelse` has an `@empty` branch for empty lists |
| `route('patient.book')` | Build a URL from a name | Throws if the name is missing |
| `@vite([...])` | CSS and JS tags | Needs `npm run dev` or `npm run build` |

`@auth` and `@guest` exist in Laravel. Dashboard pages assume the layout’s user is present because middleware already ran.

## Layouts and components

| File | Role |
|---|---|
| `layouts/public.blade.php` | Marketing and auth screens |
| `layouts/dashboard.blade.php` | Sidebar, search, notifications, `@vite` |
| `layouts/app.blade.php` | Breeze profile |
| `layouts/guest.blade.php` | Older Breeze shell. Login does not use it |
| `components/dashboard/sidebar.blade.php` | Reads `config/dashboard.php` |
| `components/dashboard/account-menu.blade.php` | Profile and security links per role |
| `components/ui/icon.blade.php` | SVG icons |
| `partials/fonts.blade.php` | Bunny font CSS for Inter |
| `partials/slot-picker.blade.php` | Shared time picker |
| `dashboard/partials/desk-booking-script.blade.php` | Receptionist booking behavior |
| `dashboard/appointments/partials/doctor-visit.blade.php` | Doctor visit body |
| `dashboard/appointments/partials/patient-visit.blade.php` | Patient visit body |
| `dashboard/appointments/partials/referral-form.blade.php` | Referral form and preview UI |

Class-based components: `App\View\Components\AppLayout`, `GuestLayout`, `SectionHeading`.

## From a variable to pixels

Example: the patient dashboard heading.

1. `Patient\DashboardController@index` loads the user.
2. The view `dashboard.patient` receives that data.
3. The template prints `Welcome back, {{ $firstName }}` (see `resources/views/dashboard/patient.blade.php`).
4. Blade escapes the name.
5. The layout wraps the section in the sidebar shell.
6. The response is HTML.
7. The browser applies Tailwind classes from the compiled CSS.

If `$firstName` is missing, Blade throws “Undefined variable”. The page becomes a 500 (or a debug page when `APP_DEBUG` is true). Fix the controller’s `compact` or array, not the CSS.

## Forms

A typical form:

```blade
<form method="POST" action="{{ route('doctor.profile.update') }}">
    @csrf
    @method('PUT')
    <input name="phone" value="{{ old('phone', $user->phone) }}">
    @error('phone')<p>{{ $message }}</p>@enderror
    <button type="submit">Save</button>
</form>
```

`old` wins after a validation error so the person does not retype. The second argument is the current database value on the first visit.

`resources/js/app.js` listens for `submit`, skips forms with `data-no-lock="true"`, and disables the clicked button. A validation error returns a fresh page, so the button is enabled again.

## JavaScript widgets

| File | Registered in `app.js` | Talks to the server? |
|---|---|---|
| `confirm-dialog.js` | `registerConfirmDialog()` | No. Asks before a submit |
| `analysis-poll.js` | Alpine | Yes. GET analysis status |
| `doctor-reviews.js` | Alpine | Yes. Review JSON and POST |
| `referral-preview.js` | Alpine | Yes. POST preview |
| `visit-chat.js` | Alpine | Yes. Message JSON and POST |
| Sidebar collapse | Inline in the dashboard layout | No. `localStorage` key `sidebar-collapsed` |
| Header patient search | Inline Alpine on the receptionist header | Yes. GET `receptionist.patients.search` |

`bootstrap.js` is imported first. It is the Axios setup from the Laravel skeleton (`window.axios`). Widgets may use Axios or `fetch`. The CSRF meta tag is how those calls should authenticate the token.

Alpine `x-data`, `x-show`, and `@click` are frontend-only until the handler calls a URL.

## Tailwind

Version **3.4.19**. Config `tailwind.config.js`. Plugin `@tailwindcss/forms`. Content paths include all Blade files, pagination views, and JS. Custom colors: `primary`, `navy`, `page`, `danger`, and others. Dashboard markup also uses arbitrary values such as `bg-[#F4F7FB]`. Those work only if the class string appears in a scanned file. A class built by concatenating strings in PHP may be missing from the CSS.

`resources/css/app.css` is the Tailwind entry (`@tailwind base/components/utilities`) plus any custom selectors. Both the source file and the Vite output matter. Editing the CSS without a running Vite dev server or a fresh build leaves the browser on the old file.

## Responsive behavior

The dashboard sidebar is off-canvas under the `lg` breakpoint (`lg:hidden` menu button, `-translate-x-full` until `navOpen`). Public pages use `sm:` and `lg:` padding. This audit did not resize a browser. The classes are the evidence.

## Loading, empty, error, success

| State | Where |
|---|---|
| Success flash | `session('status')` in the layout or form |
| Validation | `@error` and `$errors` |
| Empty lists | `@forelse` empty copy. Appointment lists are tested |
| Analysis running | Progress partial `dashboard/doctor/analysis/partials/progress.blade.php` and the poll script |
| 403/404/419/500 | Laravel default pages. No `resources/views/errors` directory |

## What belongs on the server

Slot availability, role checks, file types, and status transitions belong in PHP. Alpine may hide a tab. A hidden tab is not a security control.

## Related

[04-page-rendering-handbook.md](04-page-rendering-handbook.md), [13-debugging-playbook.md](13-debugging-playbook.md).
