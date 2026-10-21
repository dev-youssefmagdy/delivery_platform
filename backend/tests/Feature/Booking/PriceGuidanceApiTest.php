<?php

namespace Tests\Feature\Booking;

use App\Models\FarePolicy;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

/**
 * GET /routes/estimate now carries the reference price guidance (SRS §9.1): guidance only, with its factors, from the
 * policy in effect for the service and zone, and an honest reason when there is none.
 */
class PriceGuidanceApiTest extends TestCase
{
    use BuildsBookings;
    use RefreshDatabase;

    private ServiceCategory $category;

    private ServiceZone $zone;

    private User $customer;

    private FarePolicy $policy;

    private const URL = '/api/v1/routes/estimate?origin=30.05,31.25&destination=30.07,31.27';

    protected function setUp(): void
    {
        parent::setUp();
        ['category' => $this->category, 'zone' => $this->zone, 'customer' => $this->customer, 'policy' => $this->policy] = $this->bookingWorld();
        $this->actingAsCustomer($this->customer);
    }

    public function test_the_estimate_carries_low_recommended_high_and_the_factors(): void
    {
        $response = $this->getJson(self::URL.'&service_category_id='.$this->category->id)->assertOk()
            ->assertJsonPath('data.price_guidance.currency', 'EGP')
            ->assertJsonPath('data.price_guidance.formula_version', 1)
            ->assertJsonPath('data.price_guidance_unavailable_reason', null)
            ->assertJsonStructure(['data' => ['distance_m', 'duration_s', 'price_guidance' => ['low_minor', 'recommended_minor', 'high_minor', 'notice', 'factors' => [['code', 'label', 'amount_minor', 'quantity', 'unit']]]]]);

        $guidance = $response->json('data.price_guidance');
        $this->assertGreaterThanOrEqual($guidance['recommended_minor'], $guidance['high_minor']);
        $this->assertLessThanOrEqual($guidance['recommended_minor'], $guidance['low_minor']);
        $this->assertSame(['base', 'distance', 'duration'], array_slice(array_column($guidance['factors'], 'code'), 0, 3));
        $this->assertSame('km', $guidance['factors'][1]['unit']);
        $this->assertSame(0, $guidance['recommended_minor'] % 100, 'rounded to the policy step');
        $this->assertStringContainsString('ليس', $guidance['notice']);
    }

    public function test_stops_and_service_details_change_the_guidance(): void
    {
        $this->policy->forceFill(['attribute_surcharges' => [['field' => 'child_seat', 'mode' => 'fixed', 'amount_minor' => 1500, 'match' => null]]])->save();
        $url = self::URL.'&service_category_id='.$this->category->id;

        $plain = $this->getJson($url)->json('data.price_guidance.recommended_minor');
        $stops = $this->getJson($url.'&stops[]=30.06,31.26')->json('data.price_guidance.recommended_minor');
        $seat = $this->getJson($url.'&service_details[child_seat]=1')->json('data.price_guidance.recommended_minor');

        $this->assertSame(500, $stops - $plain);
        $this->assertSame(1500, $seat - $plain);
        $this->getJson($url.'&service_details[child_seat]=0')->assertJsonPath('data.price_guidance.recommended_minor', $plain);
    }

    public function test_without_a_category_there_is_no_guidance(): void
    {
        $this->getJson(self::URL)->assertOk()->assertJsonPath('data.price_guidance', null)->assertJsonPath('data.price_guidance_unavailable_reason', null);
    }

    public function test_a_service_without_an_enabled_policy_says_so(): void
    {
        $this->policy->forceFill(['active' => false])->save();
        $this->getJson(self::URL.'&service_category_id='.$this->category->id)->assertOk()
            ->assertJsonPath('data.price_guidance', null)->assertJsonPath('data.price_guidance_unavailable_reason', 'no_fare_policy');

        $this->policy->forceFill(['active' => true, 'effective_from' => now()->addDay()])->save();
        $this->getJson(self::URL.'&service_category_id='.$this->category->id)->assertJsonPath('data.price_guidance_unavailable_reason', 'no_fare_policy');

        $this->policy->forceFill(['effective_from' => now()->subDays(3), 'effective_to' => now()->subDay()])->save();
        $this->getJson(self::URL.'&service_category_id='.$this->category->id)->assertJsonPath('data.price_guidance_unavailable_reason', 'no_fare_policy');
    }

    public function test_a_service_that_turned_the_estimate_off_says_so(): void
    {
        $this->category->forceFill(['reference_estimate_enabled' => false])->save();

        $this->getJson(self::URL.'&service_category_id='.$this->category->id)->assertOk()
            ->assertJsonPath('data.price_guidance', null)->assertJsonPath('data.price_guidance_unavailable_reason', 'reference_estimate_disabled');
    }

    public function test_the_zone_policy_beats_the_category_policy_inside_the_zone(): void
    {
        $zonePolicy = FarePolicy::factory()->create(['service_category_id' => $this->category->id, 'service_zone_id' => $this->zone->id, 'base_minor' => 50_000, 'min_fare_minor' => 50_000]);
        $url = self::URL.'&service_category_id='.$this->category->id;

        $this->assertGreaterThanOrEqual(50_000, $this->getJson($url)->json('data.price_guidance.recommended_minor'));

        $zonePolicy->forceFill(['active' => false])->save();
        $this->assertLessThan(50_000, $this->getJson($url)->json('data.price_guidance.recommended_minor'));
    }

    public function test_among_several_policies_the_latest_effective_one_wins(): void
    {
        $this->policy->forceFill(['effective_from' => now()->subDays(10)])->save();
        FarePolicy::factory()->create(['service_category_id' => $this->category->id, 'base_minor' => 40_000, 'min_fare_minor' => 40_000, 'effective_from' => now()->subDay()]);

        $this->assertGreaterThanOrEqual(40_000, $this->getJson(self::URL.'&service_category_id='.$this->category->id)->json('data.price_guidance.recommended_minor'));
    }

    public function test_the_price_never_depends_on_how_many_customers_ask_or_when(): void
    {
        $url = self::URL.'&service_category_id='.$this->category->id;
        $first = $this->getJson($url)->json('data.price_guidance');

        foreach (range(1, 12) as $ignored) {
            $this->getJson($url)->assertOk();
        }
        $this->travelTo(now()->setTime(8, 30));
        $morning = $this->getJson($url)->json('data.price_guidance');
        $this->travelTo(now()->setTime(23, 45));
        $night = $this->getJson($url)->json('data.price_guidance');

        $this->assertSame($first, $morning);
        $this->assertSame($first, $night);
    }

    public function test_a_category_that_is_not_offered_here_is_still_refused(): void
    {
        $other = $this->rideCategory();

        $this->getJson(self::URL.'&service_category_id='.$other->id)->assertStatus(422)->assertJsonPath('errors._code', 'category_unavailable');
    }
}
