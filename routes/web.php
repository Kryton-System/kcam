<?php

declare(strict_types=1);

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredSchoolController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\PortalController;
    });

use App\Http\Controllers\PayrollController;
use App\Http\Controllers\StaffController;
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

    // Parent portal (read only, own children only)
    Route::prefix('portal')->name('portal.')->middleware('module:portal')->group(function (): void {
        Route::get('/', [PortalController::class, 'index'])->middleware('can:view-portal')->name('index');
        Route::get('/children/{student}', [PortalController::class, 'show'])->middleware('can:view-portal')->name('children.show');
    });

    // Accounts: expenses, monthly summary, daily cash book
    Route::prefix('accounts')->name('accounts.')->middleware('module:accounts')->group(function (): void {
        Route::get('/', [AccountsController::class, 'index'])->middleware('can:view-accounts')->name('index');
        Route::get('/expenses', [AccountsController::class, 'expenses'])->middleware('can:view-accounts')->name('expenses');
        Route::post('/expenses', [AccountsController::class, 'storeExpense'])->middleware('can:manage-expenses')->name('expenses.store');
        Route::post('/expenses/{expense}/void', [AccountsController::class, 'voidExpense'])->middleware('can:manage-expenses')->name('expenses.void');
        Route::post('/categories', [AccountsController::class, 'storeCategory'])->middleware('can:manage-expenses')->name('categories.store');
        Route::get('/cash-book', [AccountsController::class, 'cashBook'])->middleware('can:view-accounts')->name('cash-book');
    });

    // Inventory (owner and accountant)
    Route::prefix('inventory')->name('inventory.')->middleware('module:inventory')->group(function (): void {
        Route::get('/', [InventoryController::class, 'index'])->middleware('can:manage-inventory')->name('index');
        Route::post('/', [InventoryController::class, 'store'])->middleware('can:manage-inventory')->name('store');
        Route::get('/{item}', [InventoryController::class, 'show'])->middleware('can:manage-inventory')->name('show');
        Route::post('/{item}/movements', [InventoryController::class, 'storeMovement'])->middleware('can:manage-inventory')->name('movements.store');
    });

    // Staff (owner only)
    Route::prefix('staff')->name('staff.')->middleware('module:payroll')->group(function (): void {
        Route::get('/', [StaffController::class, 'index'])->middleware('can:manage-staff')->name('index');
        Route::get('/create', [StaffController::class, 'create'])->middleware('can:manage-staff')->name('create');
        Route::post('/', [StaffController::class, 'store'])->middleware('can:manage-staff')->name('store');
        Route::get('/{staff}/edit', [StaffController::class, 'edit'])->middleware('can:manage-staff')->name('edit');
        Route::put('/{staff}', [StaffController::class, 'update'])->middleware('can:manage-staff')->name('update');
    });

    // Payroll (owner only)
    Route::prefix('payroll')->name('payroll.')->middleware('module:payroll')->group(function (): void {
        Route::get('/', [PayrollController::class, 'index'])->middleware('can:manage-payroll')->name('index');
        Route::post('/generate', [PayrollController::class, 'generate'])->middleware('can:manage-payroll')->name('generate');
        Route::get('/{payslip}', [PayrollController::class, 'show'])->middleware('can:manage-payroll')->name('show');
        Route::put('/{payslip}', [PayrollController::class, 'update'])->middleware('can:manage-payroll')->name('update');
        Route::post('/{payslip}/pay', [PayrollController::class, 'pay'])->middleware('can:manage-payroll')->name('pay');
    });
});
