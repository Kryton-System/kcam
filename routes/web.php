<?php

declare(strict_types=1);

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredSchoolController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Health check endpoint for uptime monitoring & Gate E
Route::get('/health', HealthCheckController::class)->name('health');

// PWA Offline fallback
Route::view('/offline', 'offline')->name('offline');

// Guest / Authentication routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1');

    Route::get('/register', [RegisteredSchoolController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredSchoolController::class, 'store'])->middleware('throttle:5,1');
});

// Authenticated school routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Every route below needs a permission: being logged in is never enough.

    // Students
    Route::prefix('students')->name('students.')->group(function (): void {
        Route::get('/', [StudentController::class, 'index'])->middleware('can:view-students')->name('index');
        Route::get('/create', [StudentController::class, 'create'])->middleware('can:create-students')->name('create');
        Route::post('/', [StudentController::class, 'store'])->middleware('can:create-students')->name('store');
    });

    // Attendance
    Route::prefix('attendance')->name('attendance.')->group(function (): void {
        Route::get('/', [AttendanceController::class, 'index'])->middleware('can:take-attendance')->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->middleware('can:take-attendance')->name('store');
    });

    // Fees & Collection (staff only; parents get their own portal routes later)
    Route::prefix('fees')->name('fees.')->group(function (): void {
        Route::get('/', [FeeController::class, 'index'])->middleware('can:collect-fees')->name('index');
        Route::post('/{invoice}/collect', [FeeController::class, 'collect'])->middleware('can:collect-fees')->name('collect');
    });

    // Exams & Results
    Route::prefix('exams')->name('exams.')->group(function (): void {
        Route::get('/', [ExamController::class, 'index'])->middleware('can:view-exams')->name('index');
    });

    // Notices & SMS
    Route::prefix('notices')->name('notices.')->group(function (): void {
        Route::get('/', [NoticeController::class, 'index'])->middleware('can:view-notices')->name('index');
        Route::post('/', [NoticeController::class, 'store'])->middleware('can:send-notices')->name('store');
    });
});
