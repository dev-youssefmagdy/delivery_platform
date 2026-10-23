<?php

namespace App\Services\Growth;

use App\Enums\Growth\FraudFlag;
use App\Enums\Growth\ReferralAudience;
use App\Models\User;
use App\Repositories\Contracts\Growth\GrowthDataRepositoryInterface;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;

/**
 * Weak abuse signals of a referral (SRS 11.3): shared device, accounts sharing the referee's device, near-identical phone numbers,
 * the same saved card, and a referrer who suddenly refers many people. Each flag only holds the reward for a person to review (the
 * manual risk queue); it is never a punishment. Evidence is counts and booleans, never raw contact data.
 */
final class ReferralFraudChecker
{
    public function __construct(private readonly GrowthDataRepositoryInterface $data, private readonly ReferralRepositoryInterface $referrals) {}

    /**
     * @return list<array{flag: string, evidence: array<string, mixed>}>
     */
    public function check(User $referrer, User $referee, ReferralAudience $audience, bool $countVelocity = true): array
    {
        $flags = [];
        $referrerDevices = $this->data->deviceIds($referrer->id);
        $refereeDevices = $this->data->deviceIds($referee->id);
        $shared = array_values(array_intersect($referrerDevices, $refereeDevices));

        if ($shared !== []) {
            $flags[] = ['flag' => FraudFlag::SameDevice->value, 'evidence' => ['shared_devices' => count($shared)]];
        }

        $others = $this->data->usersOnDevices($refereeDevices, $referee->id);

        if (count($others) >= 2) {
            $flags[] = ['flag' => FraudFlag::LinkedAccount->value, 'evidence' => ['other_accounts_on_device' => count($others)]];
        }

        if ($this->phonePattern($referrer->phone_e164) !== null && $this->phonePattern($referrer->phone_e164) === $this->phonePattern($referee->phone_e164)) {
            $flags[] = ['flag' => FraudFlag::SamePhonePattern->value, 'evidence' => ['ignored_last_digits' => (int) config('growth.fraud.phone_pattern_ignore_last_digits', 3)]];
        }

        $cards = array_values(array_intersect($this->data->paymentCardKeys($referrer->id), $this->data->paymentCardKeys($referee->id)));

        if ($cards !== []) {
            $flags[] = ['flag' => FraudFlag::SamePaymentCard->value, 'evidence' => ['shared_cards' => count($cards)]];
        }

        if ($countVelocity) {
            $window = max(1, (int) config('growth.fraud.velocity_window_hours', 24));
            $max = max(1, (int) config('growth.fraud.velocity_max', 5));
            $recent = $this->referrals->relationshipsOfReferrerSince($referrer->id, $audience, now()->subHours($window));

            if ($recent >= $max) {
                $flags[] = ['flag' => FraudFlag::Velocity->value, 'evidence' => ['referrals_in_window' => $recent + 1, 'window_hours' => $window, 'max' => $max]];
            }
        }

        return $flags;
    }

    private function phonePattern(?string $phone): ?string
    {
        $ignore = max(1, (int) config('growth.fraud.phone_pattern_ignore_last_digits', 3));

        return $phone === null || strlen($phone) <= $ignore + 4 ? null : substr($phone, 0, -$ignore);
    }
}
