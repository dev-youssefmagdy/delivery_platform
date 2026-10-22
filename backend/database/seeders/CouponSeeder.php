<?php

namespace Database\Seeders;

use App\Enums\Coupon\CouponScopeType;
use App\Enums\Coupon\CouponStatus;
use App\Enums\Coupon\DiscountType;
use App\Enums\Coupon\FundingSource;
use App\Models\Coupon;
use App\Models\CouponVersion;
use App\Models\ServiceFamily;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Services\Coupon\CouponDefinitionFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter promotions (EGP) so the coupon manager and the apps have something to show: a percentage coupon, a fixed one, a
 * new-customer coupon, a service-restricted split-funded coupon, an automatic weekend campaign and an expired one. Idempotent:
 * a code that already exists is left exactly as an admin configured it. REVIEW BEFORE LAUNCH: these are demonstration values.
 */
class CouponSeeder extends Seeder
{
    public function run(CouponRepositoryInterface $coupons, CouponDefinitionFactory $definitions): void
    {
        $rides = ServiceFamily::query()->where('code', 'rides')->first() ?? ServiceFamily::query()->orderBy('id')->first();

        foreach ($this->definitions($rides?->id) as $definition) {
            $code = $definition['attributes']['code'] ?? null;

            if ($code !== null && $coupons->codeExists($code)) {
                continue;
            }

            if ($code === null && Coupon::query()->whereNull('code')->where('name', $definition['attributes']['name'])->exists()) {
                continue;
            }

            /** @var Coupon $coupon */
            $coupon = $coupons->create($definition['attributes'] + ['uuid' => (string) Str::uuid(), 'currency' => 'EGP', 'timezone' => 'Africa/Cairo', 'version' => 1]);
            $coupons->syncScopes($coupon, $definition['scopes']);
            $coupon->load('scopes');
            CouponVersion::query()->create(['coupon_id' => $coupon->id, 'version' => 1, 'terms' => $definitions->termsOf($coupon)->toSnapshot(), 'reason' => 'seed']);
        }
    }

    /**
     * @return list<array{attributes: array<string, mixed>, scopes: array<string, list<int>>}>
     */
    private function definitions(?int $familyId): array
    {
        $title = static fn (string $en, string $ar): array => ['en' => $en, 'ar' => $ar];

        return [
            [
                'attributes' => [
                    'code' => 'SAVE20', 'name' => '20% off up to 50 EGP', 'title' => $title('20% off your ride', 'خصم 20% على رحلتك'),
                    'description' => $title('Up to 50 EGP off.', 'حتى 50 جنيهًا خصم.'),
                    'discount_type' => DiscountType::Percentage, 'discount_value' => 2000, 'max_discount_minor' => 5000, 'min_fare_minor' => 3000,
                    'per_customer_limit' => 5, 'global_limit' => 5000, 'budget_minor' => 25_000_000, 'funding_source' => FundingSource::Platform,
                    'funding_split' => ['platform_bp' => 10_000, 'provider_bp' => 0, 'partner_bp' => 0], 'status' => CouponStatus::Active,
                    'starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(6), 'priority' => 0,
                ],
                'scopes' => [],
            ],
            [
                'attributes' => [
                    'code' => 'FLAT15', 'name' => '15 EGP off', 'title' => $title('15 EGP off', 'خصم 15 جنيهًا'),
                    'discount_type' => DiscountType::Fixed, 'discount_value' => 1500, 'min_fare_minor' => 5000, 'per_customer_limit' => 2,
                    'global_limit' => 2000, 'funding_source' => FundingSource::Platform,
                    'funding_split' => ['platform_bp' => 10_000, 'provider_bp' => 0, 'partner_bp' => 0], 'status' => CouponStatus::Active,
                    'starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(3), 'priority' => 0,
                ],
                'scopes' => [],
            ],
            [
                'attributes' => [
                    'code' => 'WELCOME', 'name' => 'Welcome: 50% off the first trip', 'title' => $title('Welcome gift: 50% off', 'هدية الترحيب: خصم 50%'),
                    'discount_type' => DiscountType::Percentage, 'discount_value' => 5000, 'max_discount_minor' => 7500, 'per_customer_limit' => 1,
                    'segments' => ['new'], 'funding_source' => FundingSource::Platform,
                    'funding_split' => ['platform_bp' => 10_000, 'provider_bp' => 0, 'partner_bp' => 0], 'status' => CouponStatus::Active,
                    'starts_at' => now()->subDay(), 'priority' => 0,
                ],
                'scopes' => [],
            ],
            [
                'attributes' => [
                    'code' => 'RIDES10', 'name' => 'Rides only: 10%, shared with drivers', 'title' => $title('10% off rides', 'خصم 10% على الرحلات'),
                    'discount_type' => DiscountType::Percentage, 'discount_value' => 1000, 'max_discount_minor' => 3000, 'per_customer_limit' => 10,
                    'funding_source' => FundingSource::Split, 'funding_split' => ['platform_bp' => 5000, 'provider_bp' => 5000, 'partner_bp' => 0],
                    'status' => CouponStatus::Active, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(2), 'priority' => 0,
                ],
                'scopes' => $familyId === null ? [] : [CouponScopeType::ServiceFamily->value => [$familyId]],
            ],
            [
                'attributes' => [
                    'name' => 'Weekend automatic 5%', 'title' => $title('Weekend offer: 5% off', 'عرض نهاية الأسبوع: خصم 5%'),
                    'discount_type' => DiscountType::Percentage, 'discount_value' => 500, 'max_discount_minor' => 2000, 'per_customer_limit' => 4,
                    'funding_source' => FundingSource::Platform, 'funding_split' => ['platform_bp' => 10_000, 'provider_bp' => 0, 'partner_bp' => 0],
                    'schedule' => [['day' => 5, 'from' => '00:00', 'to' => '23:59'], ['day' => 6, 'from' => '00:00', 'to' => '23:59']],
                    'status' => CouponStatus::Active, 'starts_at' => now()->subDay(), 'priority' => -10,
                ],
                'scopes' => [],
            ],
            [
                'attributes' => [
                    'code' => 'OLD10', 'name' => 'Expired 10 EGP off', 'title' => $title('10 EGP off', 'خصم 10 جنيهات'),
                    'discount_type' => DiscountType::Fixed, 'discount_value' => 1000, 'per_customer_limit' => 1, 'funding_source' => FundingSource::Platform,
                    'funding_split' => ['platform_bp' => 10_000, 'provider_bp' => 0, 'partner_bp' => 0], 'status' => CouponStatus::Expired,
                    'starts_at' => now()->subMonths(3), 'ends_at' => now()->subMonth(), 'priority' => 0,
                ],
                'scopes' => [],
            ],
        ];
    }
}
