<?php

namespace App\Http\Controllers\Admin\Identity;

use App\DataTables\Admin\Identity\OtpChallengesDataTable;
use App\Http\Controllers\Admin\AdminController;

/**
 * Read-only OTP challenge log (otp_challenges.view).
 */
final class OtpChallengeController extends AdminController
{
    public function index(OtpChallengesDataTable $table): mixed
    {
        return $table->render('admin.otp-challenges.index');
    }
}
