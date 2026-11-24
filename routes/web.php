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
use App\Http\Controllers\TransportController;
use App\Http\Controllers\TransportVehicleController;
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
    Route::prefix('students')->name('students.')->middleware('module:students')->group(function (): void {
        Route::get('/', [StudentController::class, 'index'])->middleware('can:view-students')->name('index');
        Route::get('/create', [StudentController::class, 'create'])->middleware('can:create-students')->name('create');
        Route::post('/', [StudentController::class, 'store'])->middleware('can:create-students')->name('store');
        Route::get('/{student}', [StudentController::class, 'show'])->middleware('can:view-students')->name('show');
        Route::get('/{student}/edit', [StudentController::class, 'edit'])->middleware('can:edit-students')->name('edit');
        Route::put('/{student}', [StudentController::class, 'update'])->middleware('can:edit-students')->name('update');
    });

    // Attendance
    Route::prefix('attendance')->name('attendance.')->middleware('module:attendance')->group(function (): void {
        Route::get('/', [AttendanceController::class, 'index'])->middleware('can:take-attendance')->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->middleware('can:take-attendance')->name('store');
    });

    // Fees & Collection (staff only; parents get their own portal routes later)
    Route::prefix('fees')->name('fees.')->middleware('module:fees')->group(function (): void {
        Route::get('/', [FeeController::class, 'index'])->middleware('can:collect-fees')->name('index');
        Route::post('/{invoice}/collect', [FeeController::class, 'collect'])->middleware('can:collect-fees')->name('collect');
    });

    // Exams & Results
    Route::prefix('exams')->name('exams.')->middleware('module:exams')->group(function (): void {
        Route::get('/', [ExamController::class, 'index'])->middleware('can:view-exams')->name('index');
        Route::get('/{exam}/marks', [ExamController::class, 'editMarks'])->middleware('can:enter-marks')->name('marks.edit');
        Route::put('/{exam}/marks', [ExamController::class, 'updateMarks'])->middleware('can:enter-marks')->name('marks.update');
    });

    // Notices & SMS
    Route::prefix('notices')->name('notices.')->middleware('module:notices')->group(function (): void {
        Route::get('/', [NoticeController::class, 'index'])->middleware('can:view-notices')->name('index');
        Route::post('/', [NoticeController::class, 'store'])->middleware('can:send-notices')->name('store');
    });

    // Transport: routes, vehicles and student assignments
    Route::prefix('transport')->name('transport.')->middleware('module:transport')->group(function (): void {
        Route::get('/', [TransportController::class, 'index'])->middleware('can:view-transport')->name('index');
        Route::get('/routes/create', [TransportController::class, 'create'])->middleware('can:manage-transport')->name('routes.create');
        Route::post('/routes', [TransportController::class, 'store'])->middleware('can:manage-transport')->name('routes.store');
        Route::get('/routes/{transportRoute}', [TransportController::class, 'show'])->middleware('can:view-transport')->name('routes.show');
        Route::get('/routes/{transportRoute}/print', [TransportController::class, 'print'])->middleware('can:view-transport')->name('routes.print');
        Route::get('/routes/{transportRoute}/edit', [TransportController::class, 'edit'])->middleware('can:manage-transport')->name('routes.edit');
        Route::put('/routes/{transportRoute}', [TransportController::class, 'update'])->middleware('can:manage-transport')->name('routes.update');
        Route::post('/routes/{transportRoute}/students', [TransportController::class, 'assign'])->middleware('can:manage-transport')->name('routes.assign');
        Route::delete('/assignments/{assignment}', [TransportController::class, 'unassign'])->middleware('can:manage-transport')->name('assignments.destroy');

        Route::get('/vehicles/create', [TransportVehicleController::class, 'create'])->middleware('can:manage-transport')->name('vehicles.create');
        Route::post('/vehicles', [TransportVehicleController::class, 'store'])->middleware('can:manage-transport')->name('vehicles.store');
        Route::get('/vehicles/{vehicle}/edit', [TransportVehicleController::class, 'edit'])->middleware('can:manage-transport')->name('vehicles.edit');
        Route::put('/vehicles/{vehicle}', [TransportVehicleController::class, 'update'])->middleware('can:manage-transport')->name('vehicles.update');
    });
});
