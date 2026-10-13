<?php

namespace Tests\Feature\Support;

use App\Models\Country;
use App\Models\Currency;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyCast;
use App\Support\Money\UnknownCurrencyException;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * currencies/countries tables, EG/EGP seed, CurrencyRegistry and MoneyCast (ADR-04).
 */
class CommonReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_egypt_and_egp_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Currency::query()->where('code', 'EGP')->count());

        $egypt = app(CountryRepositoryInterface::class)->findByIso2('eg');
        $this->assertNotNull($egypt);
        $this->assertSame('مصر', $egypt->getTranslation('name', 'ar'));
        $this->assertSame('Egypt', $egypt->getTranslation('name', 'en'));
        $this->assertSame('+20', $egypt->phone_code);
        $this->assertSame('Africa/Cairo', $egypt->timezone);
        $this->assertSame('EGP', $egypt->currency?->code);
    }

    public function test_translatable_name_falls_back_to_arabic(): void
    {
        $this->seed(DatabaseSeeder::class);
        $country = Country::factory()->create(['name' => ['ar' => 'السودان']]);

        app()->setLocale('en');

        $this->assertSame('السودان', $country->refresh()->name);
    }

    public function test_currency_registry_resolves_exponent_and_symbols_from_the_database(): void
    {
        $this->seed(DatabaseSeeder::class);

        $egp = app(CurrencyRegistry::class)->get('egp');

        $this->assertSame('EGP', $egp->code);
        $this->assertSame(2, $egp->exponent);
        $this->assertSame('ج.م', $egp->symbol('ar'));
        $this->assertFalse(app(CurrencyRegistry::class)->has('XXX'));

        $this->expectException(UnknownCurrencyException::class);
        app(CurrencyRegistry::class)->get('XXX');
    }

    public function test_money_cast_round_trips_minor_units_and_currency(): void
    {
        $this->seed(DatabaseSeeder::class);

        $model = new class extends Model
        {
            protected $guarded = [];

            protected function casts(): array
            {
                return ['fare' => MoneyCast::class.':fare_minor,currency', 'tip' => MoneyCast::class];
            }
        };

        $model->fare = Money::of(12345, app(CurrencyRegistry::class)->get('EGP'));

        $this->assertSame(12345, $model->getAttributes()['fare_minor']);
        $this->assertSame('EGP', $model->getAttributes()['currency']);
        $this->assertTrue($model->fare->equals(Money::of(12345, app(CurrencyRegistry::class)->get('EGP'))));
        $this->assertNull($model->tip);

        $model->tip = Money::of(500, app(CurrencyRegistry::class)->get('EGP'));
        $this->assertSame(500, $model->getAttributes()['tip_minor']);
    }
}
