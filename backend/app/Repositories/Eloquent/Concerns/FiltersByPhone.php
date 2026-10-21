<?php

namespace App\Repositories\Eloquent\Concerns;

use App\Models\User;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;

/**
 * Phone filter on the `users` table for listings of other records (bookings): a full international number matches
 * exactly, a run of at least four digits matches the end of the number (the masked display already shows the last four
 * digits). Anything else matches nothing, so a typo never lists everybody.
 */
trait FiltersByPhone
{
    /**
     * @param Builder<User> $query
     */
    protected function applyPhoneFilter(Builder $query, string $input): void
    {
        $latin = strtr($input, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $phone = PhoneNumber::normalize($latin);

        if ($phone !== null) {
            $query->where('phone_e164', $phone);

            return;
        }

        $digits = (string) preg_replace('/\D+/', '', $latin);

        if (strlen($digits) >= 4) {
            $query->where('phone_e164', 'like', '%'.$digits);

            return;
        }

        $query->whereRaw('1 = 0');
    }
}
