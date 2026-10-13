<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $iso2
 * @property string $name translated in the current locale; raw JSON {ar, en}
 * @property string $phone_code
 * @property string $currency_code
 * @property string $timezone
 * @property string $default_locale
 * @property bool $active
 */
class Country extends Model
{
    use Auditable;

    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['iso2', 'name', 'phone_code', 'currency_code', 'timezone', 'default_locale', 'active'];

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
