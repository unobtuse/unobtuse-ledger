<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Google OAuth routes
Route::prefix('auth/google')->group(function () {
    Route::get('/', [SocialiteController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/callback', [SocialiteController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Protected routes (require authentication and email verification)
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Accounts
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::post('/link', [AccountController::class, 'createLinkToken'])->name('link');
        Route::get('/link/callback', [AccountController::class, 'handlePlaidCallback'])->name('link.callback');
    });
});
