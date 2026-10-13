<?php

use App\Http\Controllers\Account\ProfileController as AccountProfileController;
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Doctor\ReferralController as DoctorReferralController;
use App\Http\Controllers\Patient\BookDoctorController;
use App\Http\Controllers\Patient\BookingController as PatientBookingController;
use App\Http\Controllers\Patient\DashboardController as PatientDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\BookingController as PublicBookingController;
use App\Http\Controllers\Public\DoctorController;
use App\Http\Controllers\Public\StripeWebhookController;
use App\Http\Controllers\Receptionist\AppointmentController as ReceptionistAppointmentController;
use App\Http\Controllers\Receptionist\DashboardController as ReceptionistDashboardController;
use App\Support\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.home', [
        'featuredDoctors' => PublicCatalog::featuredDoctors(),
        'specialties' => PublicCatalog::specialties(),
    ]);
})->name('home');

Route::view('/about', 'public.about')->name('about');

Route::view('/contact', 'public.contact')->name('contact');

Route::get('/services', function () {
    return view('public.services.index', [
        'specialties' => PublicCatalog::specialties(),
    ]);
})->name('services.index');

Route::view('/faq', 'public.faq')->name('faq');

Route::view('/security', 'public.security')->name('security');

Route::controller(DoctorController::class)->group(function () {
    Route::get('/doctors', 'index')->name('doctors.index');

    Route::get('/doctors/{slug}', 'show')->name('doctors.show');
});

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
        Route::get('/bookings/{appointment}/reports/{report}', [PatientBookingController::class, 'report'])
            ->scopeBindings()
            ->name('bookings.reports.show');
        Route::post('/bookings/{appointment}/cancel', [PatientBookingController::class, 'cancel'])->name('bookings.cancel');

        Route::get('/profile', [AccountProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [AccountProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AccountProfileController::class, 'updatePatient'])->name('profile.update');
    });

Route::middleware(['auth', 'role:doctor'])
    ->prefix('doctor')
    ->name('doctor.')
    ->group(function () {
        Route::get('/dashboard', [DoctorDashboardController::class, 'index'])->name('dashboard');
        Route::get('/appointments', [DoctorAppointmentController::class, 'index'])->name('appointments');
        Route::get('/appointments/{appointment}', [DoctorAppointmentController::class, 'show'])->name('appointments.show');
        Route::post('/appointments/{appointment}/status', [DoctorAppointmentController::class, 'updateStatus'])->name('appointments.update-status');
        Route::put('/appointments/{appointment}/consult', [DoctorAppointmentController::class, 'updateConsult'])->name('appointments.consult.update');
        Route::get('/appointments/{appointment}/reports/{report}', [DoctorAppointmentController::class, 'report'])
            ->scopeBindings()
            ->name('appointments.reports.show');
        Route::post('/appointments/{appointment}/referrals', [DoctorReferralController::class, 'store'])->name('referrals.store');

        Route::get('/patients', [DoctorPatientController::class, 'index'])->name('patients');
        Route::get('/patients/create', [DoctorPatientController::class, 'create'])->name('patients.create');
        Route::post('/patients', [DoctorPatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}', [DoctorPatientController::class, 'show'])->name('patients.show');

        Route::get('/referrals', [DoctorReferralController::class, 'index'])->name('referrals');
        Route::get('/referrals/{referral}', [DoctorReferralController::class, 'show'])->name('referrals.show');
        Route::post('/referrals/{referral}/accept', [DoctorReferralController::class, 'accept'])->name('referrals.accept');
        Route::post('/referrals/{referral}/decline', [DoctorReferralController::class, 'decline'])->name('referrals.decline');
        Route::get('/referrals/{referral}/reports/{report}', [DoctorReferralController::class, 'report'])->name('referrals.reports.show');

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
        Route::post('/appointments/{appointment}/status', [ReceptionistAppointmentController::class, 'updateStatus'])->name('appointments.update-status');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
