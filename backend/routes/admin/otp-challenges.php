<?php

use App\Http\Controllers\Admin\Identity\OtpChallengeController;
use App\Models\OtpChallenge;
use Illuminate\Support\Facades\Route;

/*
| Read-only OTP challenge log (otp_challenges.view). Loaded by routes/admin.php inside the authenticated admin group.
*/
Route::get('otp-challenges', [OtpChallengeController::class, 'index'])->name('otp-challenges.index')->can('viewAny', OtpChallenge::class);
