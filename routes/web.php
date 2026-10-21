<?php

use App\Http\Controllers\PreferenceController;
use Illuminate\Support\Facades\Route;

// Phase 2: foundation only. Storefront, quotes and admin routes arrive in later phases.
Route::get('/', fn () => view('welcome'))->name('home');

Route::middleware('auth')->group(function () {
    Route::put('/preferences/theme', [PreferenceController::class, 'theme'])->name('preferences.theme');
});
