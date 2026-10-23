<?php

namespace App\Services\Growth;

use App\Enums\Growth\ReferralAudience;
use App\Models\ReferralCode;
use App\Models\User;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * One permanent referral code per user and audience. The code uses an unambiguous alphabet (no 0/O/1/I) and is shared as a link.
 */
final class ReferralCodeService
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private readonly ReferralRepositoryInterface $referrals) {}

    public function ensure(User $user, ReferralAudience $audience): ReferralCode
    {
        $existing = $this->referrals->codeOf($user->id, $audience);

        if ($existing instanceof ReferralCode) {
            return $existing;
        }

        for ($attempt = 0; $attempt < 8; $attempt++) {
            try {
                return $this->referrals->createCode($user->id, $audience, $this->generate());
            } catch (UniqueConstraintViolationException) {
                // Either the same user raced with itself (then the code exists now) or the random code collided: look, then retry.
                $raced = $this->referrals->codeOf($user->id, $audience);

                if ($raced instanceof ReferralCode) {
                    return $raced;
                }
            }
        }

        throw new \RuntimeException('Could not allocate a referral code.');
    }

    public function link(ReferralCode $code): string
    {
        return rtrim((string) config('growth.link_base'), '/').'/'.$code->code;
    }

    public static function normalize(string $input): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $input) ?? '');
    }

    private function generate(): string
    {
        $length = max(6, min(12, (int) config('growth.code_length', 8)));
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }
}
