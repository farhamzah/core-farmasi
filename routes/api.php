<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeesController;
use App\Http\Controllers\Api\InternalAppAccessController;
use App\Http\Controllers\Api\InternalDirectoryController;
use App\Http\Controllers\Api\InternalLeadershipController;
use App\Http\Controllers\Api\LecturersController;
use App\Http\Controllers\Api\StudyProgramsController;
use App\Http\Controllers\Api\StudentsController;
use App\Http\Controllers\Api\TuPortalAuthVerificationController;
use App\Http\Controllers\Api\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:' . config('core_api.default_rate_limit', '60,1'))->group(function () {
    Route::get('health', fn () => response()->json(['status' => 'ok']));
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth.api'])->group(function () {
        Route::get('auth/validate-token', [AuthController::class, 'validateToken']);
        Route::post('auth/validate-token', [AuthController::class, 'validateToken']);
        Route::get('users/{id}', [UsersController::class, 'show']);
        Route::get('students/{id}', [StudentsController::class, 'show']);
        Route::get('lecturers/{id}', [LecturersController::class, 'show']);
        Route::get('employees/{id}', [EmployeesController::class, 'show']);
        Route::get('study-programs', [StudyProgramsController::class, 'index']);
        Route::get('study-programs/{id}', [StudyProgramsController::class, 'show']);

    });

    Route::prefix('internal')->group(function () {
        Route::get('apps/{app_code}/users/{user}/access', [InternalAppAccessController::class, 'show'])
            ->middleware('auth.core-api-client:read:app-access');
        Route::post('apps/tu-farmasi/portal-auth/verify', [TuPortalAuthVerificationController::class, 'verify'])
            ->middleware('auth.core-api-client:verify:tu-portal-auth');
        Route::get('leadership/current', [InternalLeadershipController::class, 'current'])
            ->middleware('auth.core-api-client:read:leadership');
        Route::prefix('directory')->group(function () {
            Route::get('users', [InternalDirectoryController::class, 'users'])
                ->middleware('auth.core-api-client:read:users');
            Route::get('users/{id}', [InternalDirectoryController::class, 'user'])
                ->middleware('auth.core-api-client:read:users');
            Route::get('students', [InternalDirectoryController::class, 'students'])
                ->middleware('auth.core-api-client:read:students');
            Route::get('students/{id}', [InternalDirectoryController::class, 'student'])
                ->middleware('auth.core-api-client:read:students');
            Route::get('lecturers', [InternalDirectoryController::class, 'lecturers'])
                ->middleware('auth.core-api-client:read:lecturers');
            Route::get('lecturers/{id}', [InternalDirectoryController::class, 'lecturer'])
                ->middleware('auth.core-api-client:read:lecturers');
            Route::get('employees', [InternalDirectoryController::class, 'employees'])
                ->middleware('auth.core-api-client:read:employees');
            Route::get('employees/{id}', [InternalDirectoryController::class, 'employee'])
                ->middleware('auth.core-api-client:read:employees');
            Route::get('study-programs', [InternalDirectoryController::class, 'studyPrograms'])
                ->middleware('auth.core-api-client:read:study-programs');
            Route::get('study-programs/{id}', [InternalDirectoryController::class, 'studyProgram'])
                ->middleware('auth.core-api-client:read:study-programs');
            Route::get('departments', [InternalDirectoryController::class, 'departments'])
                ->middleware('auth.core-api-client:read:departments');
            Route::get('departments/{id}', [InternalDirectoryController::class, 'department'])
                ->middleware('auth.core-api-client:read:departments');
        });
    });
});
