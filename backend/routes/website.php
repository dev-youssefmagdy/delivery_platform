<?php

use App\Http\Controllers\Website\TripShareController;
use Illuminate\Support\Facades\Route;

/*
| Public website (ADR-01). Middleware: `web`. Locale-prefixed pages (/{locale}/…) arrive in P5.5.
*/

Route::view('/', 'welcome')->name('website.home');

// Public trip share (P2.5, SRS §13.1): the token is the only credential, so the endpoints are throttled per IP and every
// failure looks the same. Pages are noindex and uncacheable (set by the controller).
Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('t/{token}', [TripShareController::class, 'show'])->where('token', '[A-Za-z0-9]{43}')->name('website.trip-share');
    Route::get('t/{token}/data', [TripShareController::class, 'data'])->where('token', '[A-Za-z0-9]{43}')->name('website.trip-share.data');
});
