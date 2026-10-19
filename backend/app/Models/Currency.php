<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use App\Support\Money\Currency as CurrencyValue;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property int $exponent
 * @property string $symbol_ar
 * @property string $symbol_en
 * @property bool $active
 * @property int $lock_version
 */
class Currency extends Model
{
    use Auditable;

    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    protected $fillable = ['code', 'exponent', 'symbol_ar', 'symbol_en', 'active', 'lock_version'];

    public function toValue(): CurrencyValue
    {
        return new CurrencyValue($this->code, $this->exponent, ['ar' => $this->symbol_ar, 'en' => $this->symbol_en]);
    }

    /**
     * Select option label: "EGP — ج.م / EGP".
     */
    public function optionLabel(): string
    {
        return $this->code.' — '.($this->symbol_ar === $this->symbol_en ? $this->symbol_en : $this->symbol_ar.' / '.$this->symbol_en);
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'lock_version' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exponent' => 'integer',
            'active' => 'boolean',
            'lock_version' => 'integer',
        ];
    }
}
