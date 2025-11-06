<?php

use App\Http\Controllers\PlaidWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Plaid Webhook (no auth middleware, Plaid will call this)
// Signature verification middleware ensures authenticity
Route::post('/plaid/webhook', [PlaidWebhookController::class, 'handle'])
    ->middleware('verify-plaid-webhook')
    ->name('plaid.webhook');


