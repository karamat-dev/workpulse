<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::prefix('attendance')->group(function () {
        Route::post('/punch', [AttendanceController::class, 'punch'])->middleware('perm:attendance.punch');
        Route::post('/auto-close-stale', [AttendanceController::class, 'closeStaleOpenSessions'])->middleware('perm:attendance.punch');
        Route::get('/records', [AttendanceController::class, 'regulationAttendanceRecords'])->middleware('perm:attendance.view');
        Route::get('/daily', [AttendanceController::class, 'dailyReport'])->middleware('perm:attendance.view');
        Route::get('/daily.csv', [AttendanceController::class, 'dailyReportCsv'])->middleware('perm:attendance.view');

        Route::post('/regulations', [AttendanceController::class, 'createRegulation'])->middleware('perm:attendance.view');
        Route::patch('/regulations/{code}/review', [AttendanceController::class, 'reviewRegulation'])->middleware('perm:attendance.manage');
    });

    Route::prefix('leave')->group(function () {
        Route::get('/types', [LeaveController::class, 'types'])->middleware('perm:leave.apply');
        Route::get('/my/balance', [LeaveController::class, 'myBalance'])->middleware('perm:leave.apply');
        Route::get('/my/requests', [LeaveController::class, 'myRequests'])->middleware('perm:leave.apply');
        Route::post('/apply', [LeaveController::class, 'apply'])->middleware('perm:leave.apply');

        Route::get('/pending', [LeaveController::class, 'pendingForReview'])->middleware('perm:leave.apply');
        Route::patch('/{code}/review', [LeaveController::class, 'review'])->middleware('perm:leave.approve_hr');
    });

    Route::prefix('reports')->group(function () {
        Route::get('/attendance/monthly', [ReportsController::class, 'monthlyAttendance'])->middleware('perm:reports.view');
        Route::get('/employees', [ReportsController::class, 'employees'])->middleware('perm:employees.view');
    });
});


