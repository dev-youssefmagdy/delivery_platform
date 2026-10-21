<?php

namespace App\Models;

use App\Support\Money\Money;
use App\Support\Money\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One immutable version of an offer: amount, currency, note and structured scope as the provider submitted them
 * (Plan I3). The model refuses updates and deletes; a revision is a new row with the next version number.
 *
 * @property int $id
 * @property int $offer_id
 * @property int $version
 * @property int $amount_minor
 * @property string $currency
 * @property Money|null $amount
 * @property string|null $note
 * @property array<string, mixed>|null $scope
 * @property string|null $idempotency_key
 * @property Carbon $created_at
 * @property-read BookingOffer $offer
 */
class BookingOfferVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['offer_id', 'version', 'amount', 'note', 'scope', 'idempotency_key'];

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Offer versions are append-only (Plan I3).');
        });

        static::deleting(static function (): void {
            throw new LogicException('Offer versions are append-only (Plan I3).');
        });
    }

    /**
     * @return BelongsTo<BookingOffer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(BookingOffer::class, 'offer_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'amount' => MoneyCast::class.':amount_minor,currency',
            'amount_minor' => 'integer',
            'scope' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
