<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\DoctorController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Account\ProfileController as AccountProfileController;

Route::get('/', function () {
    return view('public.home');
})->name('home');

Route::view('/about', 'public.about')->name('about');

Route::view('/contact', 'public.contact')->name('contact');

Route::view('/services', 'public.services.index')->name('services.index');

Route::view('/doctors', 'public.doctors.index')->name('doctors.index');

Route::view('/faq', 'public.faq')->name('faq');
Route::view('/security', 'public.security')->name('security');


Route::controller(DoctorController::class)->group(function () {

    Route::get('/doctors', 'index')
        ->name('doctors.index');

    Route::get('/doctors/{slug}', 'show')
        ->name('doctors.show');
});


Route::get('/dashboard', function (Request $request) {
    return match ($request->user()->role) {
        'patient' => redirect()->route('patient.dashboard'),
        'doctor' => redirect()->route('doctor.dashboard'),
        'receptionist' => redirect()->route('receptionist.dashboard'),
        default => abort(403),
    };
})->middleware('auth:sanctum')->name('dashboard');


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/patient/dashboard', function (Request $request) {
        abort_unless($request->user()->role === 'patient', 403);

        return view('dashboard.patient');
    })->name('patient.dashboard');


    Route::get('/doctor/dashboard', function (Request $request) {
        abort_unless($request->user()->role === 'doctor', 403);

        return view('dashboard.doctor');
    })->name('doctor.dashboard');


    Route::get('/receptionist/dashboard', function (Request $request) {
        abort_unless($request->user()->role === 'receptionist', 403);

        return view('dashboard.receptionist');
    })->name('receptionist.dashboard');
});


Route::middleware(['auth:sanctum', 'role:patient'])
    ->prefix('patient')
    ->name('patient.')
    ->group(function () {
        Route::get('/profile', [
            AccountProfileController::class,
            'show',
        ])->name('profile.show');

        Route::get('/profile/edit', [
            AccountProfileController::class,
            'edit',
        ])->name('profile.edit');
    });

// Doctor profile routes
Route::middleware(['auth:sanctum', 'role:doctor'])
    ->prefix('doctor')
    ->name('doctor.')
    ->group(function () {
        Route::get('/profile', [
            AccountProfileController::class,
            'show',
        ])->name('profile.show');

        Route::get('/profile/edit', [
            AccountProfileController::class,
            'edit',
        ])->name('profile.edit');
    });
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
