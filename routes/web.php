<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\BillsController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\PaySchedulesController;
use App\Http\Controllers\TransactionsController;
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
        Route::get('/oauth-callback', [AccountController::class, 'oauthCallback'])->name('oauth-callback');
        Route::post('/link-token', [AccountController::class, 'createLinkToken'])->name('link-token');
        Route::post('/exchange-token', [AccountController::class, 'exchangePublicToken'])->name('exchange-token');
        Route::post('/{account}/refresh', [AccountController::class, 'refreshBalance'])->name('refresh');
        Route::post('/{account}/sync', [AccountController::class, 'sync'])->name('sync');
        Route::patch('/{account}/nickname', [AccountController::class, 'updateNickname'])->name('update-nickname');
        Route::delete('/{account}', [AccountController::class, 'disconnect'])->name('disconnect');
    });

    // Transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionsController::class, 'index'])->name('index');
    });

    // Bills
    Route::prefix('bills')->name('bills.')->group(function () {
        Route::get('/', [BillsController::class, 'index'])->name('index');
    });

    // Pay Schedules
    Route::prefix('pay-schedules')->name('pay-schedules.')->group(function () {
        Route::get('/', [PaySchedulesController::class, 'index'])->name('index');
    });

    // Budget
    Route::prefix('budget')->name('budget.')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
    });
});
