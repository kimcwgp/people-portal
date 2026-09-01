<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\MyLeavesController;
use App\Http\Controllers\MyAttendanceController;
use App\Http\Controllers\MyStandupController;
use App\Http\Controllers\MyOvertimeController;
use App\Http\Controllers\MyShiftController;
use App\Http\Controllers\ShiftChangeRequestController;
use App\Http\Controllers\AssociateLogController;
use App\Http\Controllers\ActiveAssociatesController;
use App\Http\Controllers\HrAnnouncementController;
use App\Http\Controllers\TeamLeavesController;
use App\Http\Controllers\TeamOvertimeController;
use App\Http\Controllers\TeamStandupController;
use App\Http\Controllers\TeamAttendanceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProxyLeavesController;
use App\Http\Controllers\ProxyOvertimeController;
use App\Http\Controllers\ProxyAttendanceController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeaveCreditsController;
use App\Http\Controllers\EmployeeManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication routes (public - no auth required)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Route::post('/login/login-link', [AuthController::class, 'sendLoginLink']);
// Route::post('/login/verify/{token}', [AuthController::class, 'verifyLoginLink']);
// Route::post('/check-auto-timeout', [AuthController::class, 'checkAutoTimeOut']);

// Protected routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    // HR Management routes
    Route::prefix('hr')->group(function () {
        // HR Announcements - Granular permissions per action
        Route::get('announcements', [HrAnnouncementController::class, 'index'])
            ->middleware('permission:view hr announcements');
        Route::get('announcements/{announcement}', [HrAnnouncementController::class, 'show'])
            ->middleware('permission:view hr announcements');
        Route::post('announcements', [HrAnnouncementController::class, 'store'])
            ->middleware('permission:create hr announcements');
        Route::put('announcements/{announcement}', [HrAnnouncementController::class, 'update'])
            ->middleware('permission:edit hr announcements');
        Route::patch('announcements/{announcement}', [HrAnnouncementController::class, 'update'])
            ->middleware('permission:edit hr announcements');
        Route::delete('announcements/{announcement}', [HrAnnouncementController::class, 'destroy'])
            ->middleware('permission:delete hr announcements');
        
        Route::prefix('associate-logs')->name('associate-logs.')->group(function () {
            Route::get('/', [AssociateLogController::class, 'index'])
                ->middleware('permission:view associate logs')
                ->name('index');
            Route::post('/', [AssociateLogController::class, 'store'])
                ->middleware('permission:create associate logs')
                ->name('store');
            Route::delete('/{associateLog}', [AssociateLogController::class, 'destroy'])
                ->middleware('permission:delete associate logs')
                ->name('destroy');
            Route::get('/users', [AssociateLogController::class, 'getUsers'])
                ->middleware('permission:view associate logs')
                ->name('users');
            Route::get('/{associateLog}/download', [AssociateLogController::class, 'downloadAttachment'])
                ->middleware('permission:view associate logs')
                ->name('download');
        });

        Route::prefix('proxy-leaves')->name('proxy-leaves.')->group(function () {
            Route::get('/cutoff-periods', [ProxyLeavesController::class, 'getCutoffPeriods'])
                ->middleware('permission:view proxy leaves')
                ->name('cutoff-periods');
            Route::get('/form-data', [ProxyLeavesController::class, 'getFormData'])
                ->middleware('permission:view proxy leaves')
                ->name('form-data');
            Route::get('/export', [ProxyLeavesController::class, 'export'])
                ->middleware('permission:view proxy leaves')
                ->name('export');
            Route::get('/stats', [ProxyLeavesController::class, 'getStats'])
                ->middleware('permission:view proxy leaves')
                ->name('stats');
            Route::get('/', [ProxyLeavesController::class, 'index'])
                ->middleware('permission:view proxy leaves')
                ->name('index');
            Route::post('/', [ProxyLeavesController::class, 'store'])
                ->middleware('permission:create proxy leaves')
                ->name('store');
            Route::get('/{leave}', [ProxyLeavesController::class, 'show'])
                ->middleware('permission:view proxy leaves')
                ->name('show');
            Route::put('/{leave}', [ProxyLeavesController::class, 'update'])
                ->middleware('permission:edit proxy leaves')
                ->name('update');
            Route::delete('/{leave}', [ProxyLeavesController::class, 'destroy'])
                ->middleware('permission:delete proxy leaves')
                ->name('destroy');
        });

        Route::prefix('proxy-overtime')->name('proxy-overtime.')->group(function () {
            Route::get('/cutoff-periods', [ProxyOvertimeController::class, 'getCutoffPeriods'])
                ->middleware('permission:view proxy overtime')
                ->name('cutoff-periods');
            Route::get('/form-data', [ProxyOvertimeController::class, 'getFormData'])
                ->middleware('permission:view proxy overtime')
                ->name('form-data');
            Route::get('/export', [ProxyOvertimeController::class, 'export'])
                ->middleware('permission:view proxy overtime')
                ->name('export');
            Route::get('/stats', [ProxyOvertimeController::class, 'getStats'])
                ->middleware('permission:view proxy overtime')
                ->name('stats');
            Route::get('/', [ProxyOvertimeController::class, 'index'])
                ->middleware('permission:view proxy overtime')
                ->name('index');
            Route::post('/', [ProxyOvertimeController::class, 'store'])
                ->middleware('permission:create proxy overtime')
                ->name('store');
            Route::get('/{overtime}', [ProxyOvertimeController::class, 'show'])
                ->middleware('permission:view proxy overtime')
                ->name('show');
            Route::put('/{overtime}', [ProxyOvertimeController::class, 'update'])
                ->middleware('permission:edit proxy overtime')
                ->name('update');
            Route::delete('/{overtime}', [ProxyOvertimeController::class, 'destroy'])
                ->middleware('permission:delete proxy overtime')
                ->name('destroy');
        });

        Route::prefix('proxy-attendance')->name('proxy-attendance.')->group(function () {
            Route::get('/cutoff-periods', [ProxyAttendanceController::class, 'getCutoffPeriods'])
                ->middleware('permission:view proxy attendance')
                ->name('cutoff-periods');
            Route::get('/form-data', [ProxyAttendanceController::class, 'getFormData'])
                ->middleware('permission:view proxy attendance')
                ->name('form-data');
            Route::get('/export', [ProxyAttendanceController::class, 'export'])
                ->middleware('permission:view proxy attendance')
                ->name('export');
            Route::get('/stats', [ProxyAttendanceController::class, 'getStats'])
                ->middleware('permission:view proxy attendance')
                ->name('stats');
            Route::get('/', [ProxyAttendanceController::class, 'index'])
                ->middleware('permission:view proxy attendance')
                ->name('index');
            Route::post('/', [ProxyAttendanceController::class, 'store'])
                ->middleware('permission:create proxy attendance')
                ->name('store');
            Route::get('/{attendance}', [ProxyAttendanceController::class, 'show'])
                ->middleware('permission:view proxy attendance')
                ->name('show');
            Route::put('/{attendance}', [ProxyAttendanceController::class, 'update'])
                ->middleware('permission:edit proxy attendance')
                ->name('update');
            Route::delete('/{attendance}', [ProxyAttendanceController::class, 'destroy'])
                ->middleware('permission:delete proxy attendance')
                ->name('destroy');
        });

        // Leave Credits Management
        Route::prefix('leave-credits')->name('leave-credits.')->group(function () {
            Route::get('/', [LeaveCreditsController::class, 'index'])
                ->middleware('permission:view leave credits')
                ->name('index');
            Route::get('/export', [LeaveCreditsController::class, 'export'])
                ->middleware('permission:view leave credits')
                ->name('export');
            Route::post('/import', [LeaveCreditsController::class, 'import'])
                ->middleware('permission:edit leave credits')
                ->name('import');
            Route::put('/{user}', [LeaveCreditsController::class, 'update'])
                ->middleware('permission:edit leave credits')
                ->name('update');
            Route::post('/{user}/reset-pending', [LeaveCreditsController::class, 'resetPending'])
                ->middleware('permission:edit leave credits')
                ->name('reset-pending');
        });

        // Employee Regularization Management
        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/regularization', [EmployeeManagementController::class, 'index'])
                ->middleware('permission:edit employee regularization')
                ->name('regularization.index');
            Route::put('/{user}/regularization', [EmployeeManagementController::class, 'updateRegularization'])
                ->middleware('permission:edit employee regularization')
                ->name('regularization.update');
            Route::delete('/{user}/regularization', [EmployeeManagementController::class, 'removeRegularization'])
                ->middleware('permission:edit employee regularization')
                ->name('regularization.remove');
        });
    });
    
    // User-specific routes
    Route::prefix('user')->name('user.')->group(function () {
        // Profile routes
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])
                ->middleware('permission:view profile')
                ->name('show');
            Route::put('/', [ProfileController::class, 'update'])
                ->middleware('permission:edit profile')
                ->name('update');
            Route::get('/associate-logs/download/{associateLog}', [ProfileController::class, 'downloadAttachment'])
                ->middleware('permission:view profile')
                ->name('download-attachment');
        });

        // Dashboard routes
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])
                ->middleware('permission:view dashboard')
                ->name('index');
            Route::post('/clock', [DashboardController::class, 'clockInOut'])
                ->middleware('permission:view dashboard')
                ->name('clock');
            Route::post('/breaks/start', [DashboardController::class, 'startBreak'])
                ->middleware('permission:view dashboard')
                ->name('breaks.start');
            Route::post('/breaks/end', [DashboardController::class, 'endBreak'])
                ->middleware('permission:view dashboard')
                ->name('breaks.end');
            Route::post('/check-auto-timeout', [DashboardController::class, 'checkAutoTimeout'])
                ->middleware('permission:view dashboard')
                ->name('check-auto-timeout');
            Route::post('/leaves/{leave}/approve', [DashboardController::class, 'approveLeave'])
                ->middleware('permission:view dashboard')
                ->name('leaves.approve');
            Route::post('/leaves/{leave}/reject', [DashboardController::class, 'rejectLeave'])
                ->middleware('permission:view dashboard')
                ->name('leaves.reject');
            Route::post('/overtime/{overtime}/approve', [DashboardController::class, 'approveOvertime'])
                ->middleware('permission:view dashboard')
                ->name('overtime.approve');
            Route::post('/overtime/{overtime}/reject', [DashboardController::class, 'rejectOvertime'])
                ->middleware('permission:view dashboard')
                ->name('overtime.reject');
            Route::post('/shift-change/{shiftChangeRequest}/approve', [DashboardController::class, 'approveShiftChange'])
                ->middleware('permission:view dashboard')
                ->name('shift-change.approve');
            Route::post('/shift-change/{shiftChangeRequest}/reject', [DashboardController::class, 'rejectShiftChange'])
                ->middleware('permission:view dashboard')
                ->name('shift-change.reject');
        });

        // My Attendance routes
        Route::prefix('my-attendance')->name('my-attendance.')->group(function () {
            Route::get('/', [MyAttendanceController::class, 'index'])
                ->middleware('permission:view my attendance')
                ->name('index');
            Route::get('/export', [MyAttendanceController::class, 'export'])
                ->middleware('permission:export my attendance')
                ->name('export');
        });

        // My Leaves routes
        Route::prefix('my-leaves')->name('my-leaves.')->group(function () {
            Route::get('/stats', [MyLeavesController::class, 'getStats'])
                ->middleware('permission:view my leaves')
                ->name('stats');
            Route::get('/types', [MyLeavesController::class, 'getLeaveTypes'])
                ->middleware('permission:view my leaves')
                ->name('types');
            Route::put('/{leave}/cancel', [MyLeavesController::class, 'cancel'])
                ->middleware('permission:cancel my leaves')
                ->name('cancel');

            // View and create require different permissions
            Route::get('/', [MyLeavesController::class, 'index'])
                ->middleware('permission:view my leaves');
            Route::post('/', [MyLeavesController::class, 'store'])
                ->middleware('permission:create my leaves');
            Route::get('/{leave}', [MyLeavesController::class, 'show'])
                ->middleware('permission:view my leaves');
            Route::put('/{leave}', [MyLeavesController::class, 'update'])
                ->middleware('permission:edit my leaves');
            Route::patch('/{leave}', [MyLeavesController::class, 'update'])
                ->middleware('permission:edit my leaves');
            Route::delete('/{leave}', [MyLeavesController::class, 'destroy'])
                ->middleware('permission:edit my leaves');
        });

        // My Standup routes
        Route::prefix('my-standups')->name('my-standups.')->group(function () {
            Route::get('/recent', [MyStandupController::class, 'recent'])
                ->middleware('permission:view my standups')
                ->name('recent');
            Route::get('/date-range', [MyStandupController::class, 'getByDateRange'])
                ->middleware('permission:view my standups')
                ->name('date-range');
            Route::post('/check-existence', [MyStandupController::class, 'checkExistence'])
                ->middleware('permission:view my standups')
                ->name('check-existence');
            Route::get('/projects', [MyStandupController::class, 'getAllProjects'])
                ->middleware('permission:view my standups')
                ->name('projects');

            // Granular permissions per action
            Route::get('/', [MyStandupController::class, 'index'])
                ->middleware('permission:view my standups');
            Route::post('/', [MyStandupController::class, 'store'])
                ->middleware('permission:create my standups');
            Route::get('/{standup}', [MyStandupController::class, 'show'])
                ->middleware('permission:view my standups');
            Route::put('/{standup}', [MyStandupController::class, 'update'])
                ->middleware('permission:edit my standups');
            Route::patch('/{standup}', [MyStandupController::class, 'update'])
                ->middleware('permission:edit my standups');
            Route::delete('/{standup}', [MyStandupController::class, 'destroy'])
                ->middleware('permission:delete my standups');
        });

        // My Overtime routes
        Route::prefix('my-overtime')->name('my-overtime.')->group(function () {
            Route::get('/projects', [MyOvertimeController::class, 'getProjects'])
                ->middleware('permission:view my overtime')
                ->name('projects');
            Route::get('/approvers', [MyOvertimeController::class, 'getApprovers'])
                ->middleware('permission:view my overtime')
                ->name('approvers');
            Route::get('/supervisor-info', [MyOvertimeController::class, 'getSupervisorInfo'])
                ->middleware('permission:view my overtime')
                ->name('supervisor-info');
            Route::get('/statistics', [MyOvertimeController::class, 'statistics'])
                ->middleware('permission:view my overtime')
                ->name('statistics');
            Route::get('/project-managers', [MyOvertimeController::class, 'getProjectManagers'])
                ->middleware('permission:view my overtime')
                ->name('project-managers');
            Route::put('/{overtime}/cancel', [MyOvertimeController::class, 'cancel'])
                ->middleware('permission:cancel my overtime');
            Route::get('/stats', [MyOvertimeController::class, 'getStats'])
                ->middleware('permission:view my overtime');

            // Granular permissions per action
            Route::get('/', [MyOvertimeController::class, 'index'])
                ->middleware('permission:view my overtime');
            Route::post('/', [MyOvertimeController::class, 'store'])
                ->middleware('permission:create my overtime');
            Route::get('/{overtime}', [MyOvertimeController::class, 'show'])
                ->middleware('permission:view my overtime');
        });

        // My Shift routes
        Route::prefix('my-shift')->name('my-shift.')->group(function () {
            Route::get('/history', [MyShiftController::class, 'history'])
                ->middleware('permission:view my shift')
                ->name('history');
            Route::get('/requests', [ShiftChangeRequestController::class, 'index'])
                ->middleware('permission:view my shift')
                ->name('requests');
            Route::get('/available-shifts', [ShiftChangeRequestController::class, 'getAvailableShifts'])
                ->middleware('permission:view my shift')
                ->name('available-shifts');
            Route::post('/request-change', [ShiftChangeRequestController::class, 'store'])
                ->middleware('permission:request shift change')
                ->name('request-change');
        });

        // Shift Change Request Approval routes
        // Note: Approval/rejection access is controlled by organizational hierarchy (having subordinates)
        // rather than explicit permissions
        Route::prefix('shift-requests')->name('shift-requests.')->group(function () {
            Route::get('/pending', [ShiftChangeRequestController::class, 'getPendingRequests'])
                ->middleware('permission:view dashboard')
                ->name('pending');
            Route::put('/{shiftChangeRequest}/approve', [ShiftChangeRequestController::class, 'approve'])
                ->name('approve');
            Route::put('/{shiftChangeRequest}/reject', [ShiftChangeRequestController::class, 'reject'])
                ->name('reject');
        });

        // My Team Shift routes
        Route::prefix('my-team-shift')->name('my-team-shift.')->group(function () {
            Route::get('/requests', [ShiftChangeRequestController::class, 'getTeamRequests'])
                ->middleware('permission:view team shift')
                ->name('requests');
        });

        // My Team Attendance
        Route::prefix('my-team-attendance')->name('my-team-attendance.')->group(function () {
            Route::get('attendance', [TeamAttendanceController::class, 'index'])
                ->middleware('permission:view team attendance');
            Route::get('export', [TeamAttendanceController::class, 'export'])
                ->middleware('permission:export team attendance');
        });

        // My Team Leaves
        Route::prefix('my-team-leaves')->name('my-team-leaves.')->group(function () {
            Route::get('leaves', [TeamLeavesController::class, 'index'])
                ->middleware('permission:view team leaves')
                ->name('index');
            Route::get('export', [TeamLeavesController::class, 'export'])
                ->middleware('permission:export team leaves')
                ->name('export');
            Route::get('stats', [TeamLeavesController::class, 'getStats'])
                ->middleware('permission:view team leaves');
            Route::get('leaves/{leave}/attachment/download', [TeamLeavesController::class, 'downloadAttachment'])
                ->middleware('permission:view team leaves')
                ->name('download-attachment');
        });

        // My Team Overtime
        Route::prefix('my-team-overtime')->name('my-team-overtime.')->group(function () {
            Route::get('overtime', [TeamOvertimeController::class, 'index'])
                ->middleware('permission:view team overtime');
            Route::get('stats', [TeamOvertimeController::class, 'getStats'])
                ->middleware('permission:view team overtime');
            Route::get('export', [TeamOvertimeController::class, 'export'])
                ->middleware('permission:export team overtime');
        });

        // My Team Standup
        Route::prefix('my-team-standup')->name('my-team-standup.')->group(function () {
            Route::get('standup', [TeamStandupController::class, 'index'])
                ->middleware('permission:view team standups');
            Route::get('export', [TeamStandupController::class, 'export'])
                ->middleware('permission:export team standups');
        });

        // Active Associates route
        Route::get('/active-associates', [ActiveAssociatesController::class, 'index'])
            ->middleware('permission:view active associates')
            ->name('active-associates');

        // Leave Types routes
        Route::get('leave-types', [LeaveTypeController::class, 'index'])
            ->middleware('permission:view leave types');
        Route::post('leave-types', [LeaveTypeController::class, 'store'])
            ->middleware('permission:create leave types');
        Route::get('leave-types/{leave_type}', [LeaveTypeController::class, 'show'])
            ->middleware('permission:view leave types');
        Route::put('leave-types/{leave_type}', [LeaveTypeController::class, 'update'])
            ->middleware('permission:edit leave types');
        Route::patch('leave-types/{leave_type}', [LeaveTypeController::class, 'update'])
            ->middleware('permission:edit leave types');
        Route::delete('leave-types/{leave_type}', [LeaveTypeController::class, 'destroy'])
            ->middleware('permission:delete leave types');

        // Clients routes
        Route::get('clients', [ClientController::class, 'index'])
            ->middleware('permission:view clients');
        Route::post('clients', [ClientController::class, 'store'])
            ->middleware('permission:create clients');
        Route::get('clients/{client}', [ClientController::class, 'show'])
            ->middleware('permission:view clients');
        Route::put('clients/{client}', [ClientController::class, 'update'])
            ->middleware('permission:edit clients');
        Route::patch('clients/{client}', [ClientController::class, 'update'])
            ->middleware('permission:edit clients');
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])
            ->middleware('permission:delete clients');

        // Projects routes
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('form-data', [ProjectController::class, 'getFormData'])
                ->middleware('permission:view projects')
                ->name('form-data');
            Route::get('/', [ProjectController::class, 'index'])
                ->middleware('permission:view projects');
            Route::post('/', [ProjectController::class, 'store'])
                ->middleware('permission:create projects');
            Route::get('/{project}', [ProjectController::class, 'show'])
                ->middleware('permission:view projects');
            Route::put('/{project}', [ProjectController::class, 'update'])
                ->middleware('permission:edit projects');
            Route::patch('/{project}', [ProjectController::class, 'update'])
                ->middleware('permission:edit projects');
            Route::delete('/{project}', [ProjectController::class, 'destroy'])
                ->middleware('permission:delete projects');
        });
    });

    // Users management routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::post('/bulk-action', [UserController::class, 'bulkAction'])
            ->middleware('permission:edit users')
            ->name('bulk-action');
        Route::get('/', [UserController::class, 'index'])
            ->middleware('permission:view users');
        Route::post('/', [UserController::class, 'store'])
            ->middleware('permission:create users');
        Route::get('/{user}', [UserController::class, 'show'])
            ->middleware('permission:view users');
        Route::put('/{user}', [UserController::class, 'update'])
            ->middleware('permission:edit users');
        Route::patch('/{user}', [UserController::class, 'update'])
            ->middleware('permission:edit users');
        Route::delete('/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:delete users');
    });

    // Role Management routes
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])
            ->middleware('permission:view roles')
            ->name('index');
        Route::get('/permissions', [RoleController::class, 'getPermissions'])
            ->middleware('permission:view roles')
            ->name('permissions');
        Route::get('/stats', [RoleController::class, 'getStats'])
            ->middleware('permission:view roles')
            ->name('stats');
        Route::post('/', [RoleController::class, 'store'])
            ->middleware('permission:create roles')
            ->name('store');
        Route::get('/{role}', [RoleController::class, 'show'])
            ->middleware('permission:view roles')
            ->name('show');
        Route::put('/{role}', [RoleController::class, 'update'])
            ->middleware('permission:edit roles')
            ->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:delete roles')
            ->name('destroy');
    });

    // Shifts management routes
    Route::get('shifts', [ShiftController::class, 'index'])
        ->middleware('permission:view shifts');
    Route::post('shifts', [ShiftController::class, 'store'])
        ->middleware('permission:create shifts');
    Route::get('shifts/{shift}', [ShiftController::class, 'show'])
        ->middleware('permission:view shifts');
    Route::put('shifts/{shift}', [ShiftController::class, 'update'])
        ->middleware('permission:edit shifts');
    Route::patch('shifts/{shift}', [ShiftController::class, 'update'])
        ->middleware('permission:edit shifts');
    Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])
        ->middleware('permission:delete shifts');
});