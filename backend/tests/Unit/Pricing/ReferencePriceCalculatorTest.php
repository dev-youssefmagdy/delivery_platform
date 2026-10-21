<?php

namespace Tests\Unit\Pricing;

use App\DTOs\Pricing\FarePolicyValues;
use App\Enums\Pricing\FareFormula;
use App\Models\FarePolicy;
use App\Services\Pricing\ReferencePriceCalculator;
use App\Support\Money\Currency;
use App\Support\Money\CurrencyRegistry;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Reference price formula V1 (SRS §9.1): exact arithmetic, rounding, minimum fare, stops, surcharges and the fact that
 * nothing outside the policy and the trip can influence the answer.
 */
class ReferencePriceCalculatorTest extends TestCase
{
    private ReferencePriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(CurrencyRegistry::class, new class implements CurrencyRegistry
        {
            public function get(string $code): Currency
            {
                return Currency::of($code, 2);
            }

            public function has(string $code): bool
            {
                return true;
            }

            public function flush(): void {}
        });
        $this->calculator = $this->app->make(ReferencePriceCalculator::class);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function values(array $overrides = []): FarePolicyValues
    {
        $snapshot = $overrides + [
            'id' => 7, 'formula_version' => 1, 'currency' => 'EGP', 'base_minor' => 1000, 'per_km_minor' => 400, 'per_min_minor' => 50,
            'min_fare_minor' => 2000, 'stop_fee_minor' => 500, 'attribute_surcharges' => [], 'rounding_step_minor' => 100,
            'range_low_bp' => 8500, 'range_high_bp' => 12500,
        ];

        return FarePolicyValues::fromSnapshot($snapshot);
    }

    public function test_it_adds_base_distance_and_duration_and_rounds_to_the_step(): void
    {
        // 1000 + 400 x 10.0 km + 50 x 20 min = 6000 -> exactly 60.00
        $guidance = $this->calculator->calculate($this->values(), 10_000, 1200, 0);

        $this->assertSame(6000, $guidance->recommended->amount);
        $this->assertSame(5100, $guidance->low->amount);
        $this->assertSame(7500, $guidance->high->amount);
        $this->assertSame(['base', 'distance', 'duration'], array_map(static fn ($line): string => $line->factor->value, $guidance->factors));
        $this->assertSame(FareFormula::V1, $guidance->formula);
        $this->assertSame(7, $guidance->farePolicyId);
    }

    public function test_fractions_are_rounded_half_up_once_at_each_component(): void
    {
        // 3.3 km x 4.00 = 13.20 -> 1320; 7 min x 0.50 = 3.50 -> 350; total 1000 + 1320 + 350 = 2670 -> rounds to 2700
        $guidance = $this->calculator->calculate($this->values(), 3300, 420, 0);

        $this->assertSame(1320, $guidance->factors[1]->amount->amount);
        $this->assertSame(350, $guidance->factors[2]->amount->amount);
        $this->assertSame(2700, $guidance->recommended->amount);
        // exactly half a step rounds up
        $this->assertSame(1100, ReferencePriceCalculator::roundToStep(1050, 100));
        $this->assertSame(1000, ReferencePriceCalculator::roundToStep(1049, 100));
        $this->assertSame(1049, ReferencePriceCalculator::roundToStep(1049, 1));
    }

    public function test_the_minimum_fare_is_a_floor_and_shows_as_a_factor(): void
    {
        $guidance = $this->calculator->calculate($this->values(), 500, 60, 0);

        $this->assertSame(2000, $guidance->recommended->amount);
        $factors = $guidance->factors;
        $this->assertSame('minimum_fare', $factors[array_key_last($factors)]->factor->value);
        $this->assertSame(array_sum(array_map(static fn ($line): int => $line->amount->amount, $guidance->factors)), 2000);
    }

    public function test_stops_are_charged_per_stop(): void
    {
        $without = $this->calculator->calculate($this->values(), 10_000, 1200, 0);
        $with = $this->calculator->calculate($this->values(), 10_000, 1200, 2);

        $this->assertSame(1000, $with->recommended->amount - $without->recommended->amount);
        $this->assertContains('stops', array_map(static fn ($line): string => $line->factor->value, $with->factors));
        $this->assertNotContains('stops', array_map(static fn ($line): string => $line->factor->value, $without->factors));
    }

    public function test_surcharges_by_attribute(): void
    {
        $values = $this->values(['attribute_surcharges' => [
            ['field' => 'child_seat', 'mode' => 'fixed', 'amount_minor' => 1500, 'match' => null, 'label' => ['ar' => 'مقعد أطفال', 'en' => 'Child seat']],
            ['field' => 'helpers', 'mode' => 'per_unit', 'amount_minor' => 2500, 'match' => null],
            ['field' => 'size', 'mode' => 'fixed', 'amount_minor' => 1000, 'match' => 'large'],
        ]]);

        $none = $this->calculator->calculate($values, 10_000, 1200, 0, ['child_seat' => false, 'helpers' => 0, 'size' => 'small']);
        $all = $this->calculator->calculate($values, 10_000, 1200, 0, ['child_seat' => true, 'helpers' => 2, 'size' => 'large']);

        $this->assertSame(6000, $none->recommended->amount);
        $this->assertSame(6000 + 1500 + 5000 + 1000, $all->recommended->amount);
        $surcharges = array_values(array_filter($all->factors, static fn ($line): bool => $line->factor->value === 'surcharge'));
        $this->assertCount(3, $surcharges);
        $this->assertSame('2', $surcharges[1]->quantity);
        $this->assertSame('مقعد أطفال', $surcharges[0]->toArray()['label']);
    }

    public function test_truthiness_of_a_fixed_surcharge_answer(): void
    {
        $values = $this->values(['attribute_surcharges' => [['field' => 'flag', 'mode' => 'fixed', 'amount_minor' => 1000, 'match' => null]]]);
        $base = $this->calculator->calculate($values, 10_000, 1200, 0)->recommended->amount;

        foreach ([true, 1, '1', 'yes', 'x', ['a']] as $truthy) {
            $this->assertSame($base + 1000, $this->calculator->calculate($values, 10_000, 1200, 0, ['flag' => $truthy])->recommended->amount, json_encode($truthy));
        }

        foreach ([false, 0, '0', 'no', 'false', '', null, []] as $falsy) {
            $this->assertSame($base, $this->calculator->calculate($values, 10_000, 1200, 0, ['flag' => $falsy])->recommended->amount, json_encode($falsy));
        }
    }

    public function test_the_range_is_ordered_and_never_crosses_the_recommendation(): void
    {
        $guidance = $this->calculator->calculate($this->values(['range_low_bp' => 10_000, 'range_high_bp' => 10_000]), 10_000, 1200, 0);

        $this->assertSame($guidance->recommended->amount, $guidance->low->amount);
        $this->assertSame($guidance->recommended->amount, $guidance->high->amount);

        $wide = $this->calculator->calculate($this->values(['range_low_bp' => 5000, 'range_high_bp' => 20_000]), 10_000, 1200, 0);
        $this->assertLessThan($wide->recommended->amount, $wide->low->amount);
        $this->assertGreaterThan($wide->recommended->amount, $wide->high->amount);
    }

    public function test_same_inputs_give_the_same_answer_and_it_is_explained_by_the_snapshot(): void
    {
        $first = $this->calculator->calculate($this->values(), 8200, 1500, 1);
        $this->travel(5)->hours();
        $second = $this->calculator->calculate($this->values(), 8200, 1500, 1);

        $this->assertSame($first->toSnapshot(), $second->toSnapshot());
    }

    public function test_the_calculator_has_no_demand_or_time_input(): void
    {
        $parameters = array_map(static fn ($parameter): string => $parameter->getName(), (new ReflectionMethod(ReferencePriceCalculator::class, 'calculate'))->getParameters());

        $this->assertSame(['policy', 'distanceMeters', 'durationSeconds', 'stopCount', 'serviceDetails', 'farePolicyId'], $parameters);

        $source = (string) file_get_contents((new ReflectionMethod(ReferencePriceCalculator::class, 'calculate'))->getFileName());
        $code = preg_replace('~/\*.*?\*/~s', '', $source) ?? '';

        foreach (['surge', 'demand', 'multiplier', 'now()', 'Carbon', 'peak'] as $word) {
            $this->assertStringNotContainsString($word, strtolower($code), "the calculator must not mention [{$word}]");
        }
    }

    public function test_policy_snapshot_round_trips_to_the_same_values(): void
    {
        $policy = FarePolicy::factory()->make(['id' => 3, 'lock_version' => 2, 'attribute_surcharges' => [['field' => 'x', 'mode' => 'fixed', 'amount_minor' => 100, 'match' => null]]]);
        $policy->id = 3;
        $values = FarePolicyValues::fromSnapshot($policy->snapshot());

        $this->assertEquals(FarePolicyValues::fromModel($policy), $values);
    }
}
