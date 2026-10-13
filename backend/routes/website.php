<?php

use Illuminate\Support\Facades\Route;

/*
| Public website (ADR-01). Middleware: `web`. Locale-prefixed pages (/{locale}/…) arrive in P5.5.
*/

Route::view('/', 'welcome')->name('website.home');
