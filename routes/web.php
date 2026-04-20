<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\WorkpulseBootstrapController;
use App\Http\Controllers\WorkpulseAppController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\AnnouncementsController;
use App\Http\Controllers\HolidaysController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\ShiftsController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/workpulse', WorkpulseAppController::class)
    ->name('workpulse');

Route::middleware('auth')->group(function () {
    Route::prefix('api')->group(function () {
        Route::get('/bootstrap', WorkpulseBootstrapController::class);
        Route::get('/me/profile', [MeController::class, 'profile']);
        Route::patch('/me/account', [MeController::class, 'updateAccount']);

        Route::prefix('employees')->group(function () {
            Route::post('/', [EmployeesController::class, 'store'])->middleware('perm:employees.manage');
            Route::get('/{employeeCode}', [EmployeesController::class, 'show'])->middleware('perm:employees.view');
            Route::patch('/{employeeCode}', [EmployeesController::class, 'update'])->middleware('perm:employees.manage');
            Route::delete('/{employeeCode}/cnic-document', [EmployeesController::class, 'deleteCnicDocument'])->middleware('perm:employees.manage');
            Route::delete('/{employeeCode}', [EmployeesController::class, 'destroy'])->middleware('perm:employees.manage');
        });

        Route::post('/announcements', [AnnouncementsController::class, 'store'])->middleware('perm:announcements.manage');
        Route::post('/holidays', [HolidaysController::class, 'store'])->middleware('perm:leave.manage');

        Route::prefix('shifts')->group(function () {
            Route::get('/', [ShiftsController::class, 'index'])->middleware('perm:employees.manage');
            Route::post('/', [ShiftsController::class, 'store'])->middleware('perm:employees.manage');
            Route::patch('/{shiftId}', [ShiftsController::class, 'update'])->middleware('perm:employees.manage');
            Route::delete('/{shiftId}', [ShiftsController::class, 'destroy'])->middleware('perm:employees.manage');
        });

        Route::prefix('transfer')->group(function () {
            Route::get('/export', [TransferController::class, 'export'])->middleware('perm:employees.manage');
            Route::get('/employees/export', [TransferController::class, 'exportEmployees'])->middleware('perm:employees.manage');
            Route::post('/employees/import', [TransferController::class, 'importEmployees'])->middleware('perm:employees.manage');
        });

        Route::prefix('attendance')->group(function () {
            Route::post('/punch', [AttendanceController::class, 'punch'])->middleware('perm:attendance.punch');
            Route::get('/live', [AttendanceController::class, 'liveStatus'])->middleware('perm:attendance.view');
            Route::get('/daily', [AttendanceController::class, 'dailyReport'])->middleware('perm:attendance.view');
            Route::get('/daily.csv', [AttendanceController::class, 'dailyReportCsv'])->middleware('perm:attendance.view');
            Route::post('/regulations', [AttendanceController::class, 'createRegulation'])->middleware('perm:attendance.view');
            Route::delete('/regulations/{code}', [AttendanceController::class, 'destroyRegulation'])->middleware('perm:attendance.view');
            Route::patch('/regulations/{code}/review', [AttendanceController::class, 'reviewRegulation'])->middleware('perm:attendance.manage');
        });

        Route::prefix('leave')->group(function () {
            Route::get('/types', [LeaveController::class, 'types'])->middleware('perm:leave.apply');
            Route::post('/types', [LeaveController::class, 'storeType'])->middleware('perm:leave.manage');
            Route::patch('/types/{code}', [LeaveController::class, 'updateType'])->middleware('perm:leave.manage');
            Route::delete('/types/{code}', [LeaveController::class, 'destroyType'])->middleware('perm:leave.manage');
            Route::get('/policies', [LeaveController::class, 'policies'])->middleware('perm:leave.apply');
            Route::get('/my/balance', [LeaveController::class, 'myBalance'])->middleware('perm:leave.apply');
            Route::get('/my/requests', [LeaveController::class, 'myRequests'])->middleware('perm:leave.apply');
            Route::post('/apply', [LeaveController::class, 'apply'])->middleware('perm:leave.apply');
            Route::get('/pending', [LeaveController::class, 'pendingForReview'])->middleware('perm:leave.apply');
            Route::get('/balances/{employeeCode}', [LeaveController::class, 'employeeBalance'])->middleware('perm:leave.manage');
            Route::put('/balances/{employeeCode}', [LeaveController::class, 'updateEmployeeBalance'])->middleware('perm:leave.manage');
            Route::put('/policies', [LeaveController::class, 'updatePolicies'])->middleware('perm:leave.manage');
            Route::patch('/{code}/review', [LeaveController::class, 'review'])->middleware('perm:leave.apply');
        });

        Route::prefix('reports')->group(function () {
            Route::get('/attendance/monthly', [ReportsController::class, 'monthlyAttendance'])->middleware('perm:reports.view');
            Route::get('/attendance/monthly.csv', [ReportsController::class, 'monthlyAttendanceCsv'])->middleware('perm:reports.view');
            Route::get('/employees', [ReportsController::class, 'employees'])->middleware('perm:employees.view');
            Route::get('/employees.csv', [ReportsController::class, 'employeesCsv'])->middleware('perm:employees.view');
        });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
