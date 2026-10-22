<?php

namespace App\Models;

use App\Enums\Finance\DiscountFunding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Who paid how much of a redemption. `kind` is `accrued` at commit and `reversed` when a refund restores the coupon.
 * `journal_entry_id` links the ledger entry that moved the money once the trip is settled.
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $redemption_id
 * @property int $booking_id
 * @property DiscountFunding $funder
 * @property string $kind
 * @property int $amount_minor
 * @property string $currency
 * @property int|null $journal_entry_id
 * @property Carbon $created_at
 */
class PromotionFundingEntry extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['coupon_id', 'redemption_id', 'booking_id', 'funder', 'kind', 'amount_minor', 'currency', 'journal_entry_id'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['funder' => DiscountFunding::class, 'amount_minor' => 'integer', 'created_at' => 'datetime'];
    }
}
