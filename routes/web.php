<?php

use App\Http\Controllers\AccountRequestController;
use App\Http\Controllers\ProfileAuthController;
use App\Http\Controllers\ProfilePasswordResetController;
use App\Http\Controllers\ProfilePortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/account-request', [AccountRequestController::class, 'create'])->name('account-request.create');
Route::post('/account-request', [AccountRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('account-request.store');
Route::get('/account-request/success', [AccountRequestController::class, 'success'])->name('account-request.success');
Route::redirect('/register', '/account-request')->name('register');

Route::get('/profile/login', [ProfileAuthController::class, 'showLoginForm'])->name('profile.login');
Route::post('/profile/login', [ProfileAuthController::class, 'login'])->middleware('throttle:10,1')->name('profile.login.store');
Route::get('/profile/forgot-password', [ProfilePasswordResetController::class, 'requestForm'])->name('profile.password.request');
Route::post('/profile/forgot-password', [ProfilePasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('profile.password.email');
Route::get('/profile/reset-password/{token}', [ProfilePasswordResetController::class, 'resetForm'])->name('profile.password.reset.edit');
Route::post('/profile/reset-password', [ProfilePasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('profile.password.reset.update');
Route::redirect('/profil-saya/login', '/profile/login')->name('profile.login.local');
Route::redirect('/profil-saya/lupa-password', '/profile/forgot-password')->name('profile.password.request.local');

Route::middleware('auth')->group(function (): void {
    Route::post('/profile/logout', [ProfileAuthController::class, 'logout'])->name('profile.logout');
    Route::get('/profile', [ProfilePortalController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfilePortalController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfilePortalController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ProfilePortalController::class, 'changePassword'])->name('profile.password.edit');
    Route::put('/profile/change-password', [ProfilePortalController::class, 'updatePassword'])->name('profile.password.update');
    Route::redirect('/profil-saya', '/profile')->name('profile.local');
    Route::redirect('/profil-saya/ganti-password', '/profile/change-password')->name('profile.password.local');
});
