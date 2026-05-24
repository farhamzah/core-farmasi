<?php

use App\Http\Controllers\ProfilePortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfilePortalController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfilePortalController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfilePortalController::class, 'update'])->name('profile.update');
    Route::redirect('/profil-saya', '/profile')->name('profile.local');
});
