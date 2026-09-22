<?php

use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Payment webhook
|--------------------------------------------------------------------------
| No auth, no session, no CSRF — the gateway POSTs here directly. The
| controller verifies the X-Webbliss-Signature HMAC before trusting anything.
| URL: https://yourdomain.com/api/wallet/webhook
*/
Route::post('/wallet/webhook', [WalletController::class, 'webhook'])->name('wallet.webhook');