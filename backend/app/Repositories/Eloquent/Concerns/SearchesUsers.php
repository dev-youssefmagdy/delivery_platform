<?php

namespace App\Repositories\Eloquent\Concerns;

use App\Models\User;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;

/**
 * One search box for uuid, phone and name on the `users` table (customers and providers share it): a uuid matches
 * exactly, a full international number matches exactly, a run of at least four digits matches the END of the number
 * (the mask already shows the last four digits, so this reveals nothing more), anything else is a name search.
 */
trait SearchesUsers
{
    private const USER_UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /**
     * @param Builder<User> $query
     */
    protected function applyUserSearch(Builder $query, string $search): void
    {
        if (self::looksLikeUuid($search)) {
            $query->where('uuid', strtolower($search));

            return;
        }

        $phone = PhoneNumber::normalize($search);

        if ($phone !== null) {
            $query->where('phone_e164', $phone);

            return;
        }

        $latin = strtr($search, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);

        if (preg_match('/^[0-9\s+\-().]{4,}$/', $latin) === 1 && strlen((string) preg_replace('/\D+/', '', $latin)) >= 4) {
            $query->where('phone_e164', 'like', '%'.preg_replace('/\D+/', '', $latin));

            return;
        }

        $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
    }

    protected static function looksLikeUuid(string $search): bool
    {
        return preg_match(self::USER_UUID_PATTERN, $search) === 1;
    }
}
