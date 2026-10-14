<?php

use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Account\ProfileController as AccountProfileController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Doctor\AiAnalysisController;
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Doctor\AvailabilityController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Doctor\PracticeController as DoctorPracticeController;
use App\Http\Controllers\Doctor\ReceptionistController as DoctorReceptionistController;
use App\Http\Controllers\Doctor\ReferralController as DoctorReferralController;
use App\Http\Controllers\Patient\BookDoctorController;
use App\Http\Controllers\Patient\BookingController as PatientBookingController;
use App\Http\Controllers\Patient\DashboardController as PatientDashboardController;
use App\Http\Controllers\Patient\HistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\BookingController as PublicBookingController;
use App\Http\Controllers\Public\DoctorController;
use App\Http\Controllers\Public\ReviewController;
use App\Http\Controllers\Public\StripeWebhookController;
use App\Http\Controllers\Receptionist\AppointmentController as ReceptionistAppointmentController;
use App\Http\Controllers\Receptionist\DashboardController as ReceptionistDashboardController;
use App\Http\Controllers\Receptionist\PatientController as ReceptionistPatientController;
use App\Support\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.home', [
        'featuredDoctors' => PublicCatalog::featuredDoctors(),
        'services' => PublicCatalog::services(),
    ]);
})->name('home');

Route::view('/about', 'public.about')->name('about');

Route::view('/contact', 'public.contact')->name('contact');

Route::get('/services', function () {
    return view('public.services.index', [
        'services' => PublicCatalog::paginatedServices(),
    ]);
})->name('services.index');

Route::view('/faq', 'public.faq')->name('faq');

Route::view('/security', 'public.security')->name('security');

Route::controller(DoctorController::class)->group(function () {
    Route::get('/doctors', 'index')->name('doctors.index');

    Route::get('/doctors/{slug}', 'show')->name('doctors.show');
});

Route::get('/doctors/{doctor}/reviews', [ReviewController::class, 'index'])->name('doctors.reviews.index');
Route::get('/doctors/{doctor}/reviews/begin', [ReviewController::class, 'begin'])->name('doctors.reviews.begin');
Route::post('/doctors/{doctor}/reviews', [ReviewController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('doctors.reviews.store');

Route::get('/doctors/{doctor}/book', [PublicBookingController::class, 'create'])->name('doctors.book');
Route::post('/doctors/{doctor}/book/continue', [PublicBookingController::class, 'remember'])->name('doctors.book.remember');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware(['auth', 'role:patient'])->group(function () {
    Route::post('/doctors/{doctor}/book', [PublicBookingController::class, 'store'])->name('doctors.book.store');
    Route::get('/checkout/appointments/{appointment}/success', [PublicBookingController::class, 'success'])->name('checkout.success');
});

Route::get('/dashboard', function (Request $request) {
    return match ($request->user()->role) {
        'patient' => redirect()->route('patient.dashboard'),
        'doctor' => redirect()->route('doctor.dashboard'),
        'receptionist' => redirect()->route('receptionist.dashboard'),
        'admin' => redirect()->route('admin.dashboard'),
        default => abort(403),
    };
})->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'role:patient'])
    ->prefix('patient')
    ->name('patient.')
    ->group(function () {
        Route::get('/dashboard', [PatientDashboardController::class, 'index'])->name('dashboard');
        Route::get('/book', [BookDoctorController::class, 'index'])->name('book');
        Route::get('/book/{doctor}', [BookDoctorController::class, 'create'])->name('book.create');
        Route::get('/bookings', [PatientBookingController::class, 'index'])->name('bookings');
        Route::get('/bookings/{appointment}', [PatientBookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{appointment}/reports', [PatientBookingController::class, 'storeReports'])->name('bookings.reports.store');
        Route::delete('/bookings/{appointment}/reports/{report}', [PatientBookingController::class, 'destroyReport'])
            ->scopeBindings()
            ->name('bookings.reports.destroy');
        Route::get('/bookings/{appointment}/messages', [PatientBookingController::class, 'messages'])->name('bookings.messages.index');
        Route::post('/bookings/{appointment}/messages', [PatientBookingController::class, 'storeMessage'])->name('bookings.messages.store');
        Route::get('/bookings/{appointment}/reports/{report}', [PatientBookingController::class, 'report'])
            ->scopeBindings()
            ->name('bookings.reports.show');
        Route::post('/bookings/{appointment}/cancel', [PatientBookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{appointment}/reschedule', [PatientBookingController::class, 'reschedule'])->name('bookings.reschedule');
        Route::get('/history', [HistoryController::class, 'index'])->name('history');
        Route::get('/history/{appointment}', [HistoryController::class, 'show'])->name('history.show');

        Route::get('/profile', [AccountProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [AccountProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AccountProfileController::class, 'updatePatient'])->name('profile.update');
    });

Route::middleware(['auth', 'role:doctor'])
    ->prefix('doctor')
    ->name('doctor.')
    ->group(function () {
        Route::get('/dashboard', [DoctorDashboardController::class, 'index'])->name('dashboard');
        Route::get('/availability', [AvailabilityController::class, 'edit'])->name('availability');
        Route::put('/availability', [AvailabilityController::class, 'update'])->name('availability.update');
        Route::post('/availability/exceptions', [AvailabilityController::class, 'storeException'])->name('availability.exceptions.store');
        Route::delete('/availability/exceptions/{exception}', [AvailabilityController::class, 'destroyException'])->name('availability.exceptions.destroy');

        Route::get('/appointments', [DoctorAppointmentController::class, 'index'])->name('appointments');
        Route::get('/appointments/{appointment}', [DoctorAppointmentController::class, 'show'])->name('appointments.show');
        Route::get('/appointments/{appointment}/analysis/{analysis}/status', [AiAnalysisController::class, 'status'])->name('appointments.analysis.status');
        Route::put('/appointments/{appointment}/analysis/{analysis}/review', [AiAnalysisController::class, 'update'])
            ->middleware('throttle:20,1')
            ->name('appointments.analysis.review');
        Route::post('/appointments/{appointment}/analysis/{analysis}/approve', [AiAnalysisController::class, 'approve'])
            ->middleware('throttle:20,1')
            ->name('appointments.analysis.approve');
        Route::post('/appointments/{appointment}/analysis', [AiAnalysisController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('appointments.analysis.store');
        Route::get('/appointments/{appointment}/analysis/{analysis?}', [AiAnalysisController::class, 'show'])->name('appointments.analysis');
        Route::post('/appointments/{appointment}/status', [DoctorAppointmentController::class, 'updateStatus'])->name('appointments.update-status');
        Route::get('/appointments/{appointment}/messages', [DoctorAppointmentController::class, 'messages'])->name('appointments.messages.index');
        Route::post('/appointments/{appointment}/messages', [DoctorAppointmentController::class, 'storeMessage'])->name('appointments.messages.store');
        Route::put('/appointments/{appointment}/consult', [DoctorAppointmentController::class, 'updateConsult'])->name('appointments.consult.update');
        Route::get('/appointments/{appointment}/reports/{report}', [DoctorAppointmentController::class, 'report'])
            ->scopeBindings()
            ->name('appointments.reports.show');
        Route::post('/appointments/{appointment}/referrals', [DoctorReferralController::class, 'store'])->name('referrals.store');
        Route::post('/appointments/{appointment}/referrals/preview', [DoctorReferralController::class, 'storePreview'])->name('referrals.preview.store');

        Route::get('/patients', [DoctorPatientController::class, 'index'])->name('patients');
        Route::get('/patients/create', [DoctorPatientController::class, 'create'])->name('patients.create');
        Route::get('/patients/search', [DoctorPatientController::class, 'search'])->name('patients.search');
        Route::post('/patients', [DoctorPatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}/analyses', [AiAnalysisController::class, 'forPatient'])->name('patients.analyses');
        Route::get('/patients/{patient}', [DoctorPatientController::class, 'show'])->name('patients.show');
        Route::put('/patients/{patient}', [DoctorPatientController::class, 'update'])->name('patients.update');

        Route::get('/receptionists', [DoctorReceptionistController::class, 'index'])->name('receptionists');
        Route::get('/receptionists/create', [DoctorReceptionistController::class, 'create'])->name('receptionists.create');
        Route::post('/receptionists', [DoctorReceptionistController::class, 'store'])->name('receptionists.store');
        Route::delete('/receptionists/{receptionist}', [DoctorReceptionistController::class, 'destroy'])->name('receptionists.destroy');

        Route::get('/referrals', [DoctorReferralController::class, 'index'])->name('referrals');
        Route::get('/referrals/preview/{preview}', [DoctorReferralController::class, 'preview'])
            ->where('preview', 'sent-[A-Za-z0-9]+|sample-[a-z0-9-]+')
            ->name('referrals.preview');
        Route::get('/referrals/{referral}', [DoctorReferralController::class, 'show'])->name('referrals.show');
        Route::post('/referrals/{referral}/accept', [DoctorReferralController::class, 'accept'])->name('referrals.accept');
        Route::post('/referrals/{referral}/decline', [DoctorReferralController::class, 'decline'])->name('referrals.decline');
        Route::post('/referrals/{referral}/schedule', [DoctorReferralController::class, 'schedule'])->name('referrals.schedule');
        Route::get('/referrals/{referral}/reports/{report}', [DoctorReferralController::class, 'report'])->name('referrals.reports.show');

        Route::get('/consultations', [DoctorPracticeController::class, 'consultations'])->name('consultations');
        Route::get('/services', [DoctorPracticeController::class, 'services'])->name('services');
        Route::get('/reports', [DoctorPracticeController::class, 'reports'])->name('reports');
        Route::get('/reviews', [DoctorPracticeController::class, 'reviews'])->name('reviews');

        Route::get('/profile', [AccountProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [AccountProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AccountProfileController::class, 'updateDoctor'])->name('profile.update');
    });

Route::middleware(['auth', 'role:receptionist'])
    ->prefix('receptionist')
    ->name('receptionist.')
    ->group(function () {
        Route::get('/dashboard', [ReceptionistDashboardController::class, 'index'])->name('dashboard');
        Route::get('/appointments', [ReceptionistAppointmentController::class, 'index'])->name('appointments');
        Route::get('/appointments/create', [ReceptionistAppointmentController::class, 'create'])->name('appointments.create');
        Route::post('/appointments', [ReceptionistAppointmentController::class, 'store'])->name('appointments.store');
        Route::get('/appointments/{appointment}', [ReceptionistAppointmentController::class, 'show'])->name('appointments.show');
        Route::post('/appointments/{appointment}/status', [ReceptionistAppointmentController::class, 'updateStatus'])->name('appointments.update-status');
        Route::post('/appointments/{appointment}/reschedule', [ReceptionistAppointmentController::class, 'reschedule'])->name('appointments.reschedule');
        Route::get('/patients/search', [ReceptionistPatientController::class, 'search'])->name('patients.search');
        Route::get('/patients', [ReceptionistPatientController::class, 'index'])->name('patients');
        Route::get('/profile', [AccountProfileController::class, 'showReceptionist'])->name('profile');
        Route::get('/profile/edit', [AccountProfileController::class, 'editReceptionist'])->name('profile.edit');
        Route::put('/profile', [AccountProfileController::class, 'updateReceptionist'])->name('profile.update');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/doctors', [AdminDashboardController::class, 'doctors'])->name('doctors');
        Route::post('/doctors/{doctor}/approve', [AdminDashboardController::class, 'approve'])->name('doctors.approve');
        Route::post('/doctors/{doctor}/reject', [AdminDashboardController::class, 'reject'])->name('doctors.reject');
        Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
        Route::patch('/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('users.update');
        Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('settings');
        Route::put('/settings', [AdminDashboardController::class, 'updateSettings'])->name('settings.update');
    });

Route::middleware('auth')->group(function () {
    Route::get('/account/security', [SecurityController::class, 'edit'])->name('account.security');
    Route::put('/account/password', [SecurityController::class, 'update'])->name('account.password.update');
    Route::get('/account/notifications', [NotificationController::class, 'index'])->name('account.notifications');
    Route::post('/account/notifications/read', [NotificationController::class, 'readAll'])->name('account.notifications.read-all');
    Route::get('/account/notifications/{notification}', [NotificationController::class, 'read'])->name('account.notifications.read');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
