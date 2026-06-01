<?php

use App\Http\Controllers\AccountRequestController;
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

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfilePortalController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfilePortalController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfilePortalController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ProfilePortalController::class, 'changePassword'])->name('profile.password.edit');
    Route::put('/profile/change-password', [ProfilePortalController::class, 'updatePassword'])->name('profile.password.update');
    Route::redirect('/profil-saya', '/profile')->name('profile.local');
    Route::redirect('/profil-saya/ganti-password', '/profile/change-password')->name('profile.password.local');
});
