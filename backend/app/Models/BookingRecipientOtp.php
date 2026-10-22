<?php

namespace App\Models;

use App\Enums\Booking\RecipientOtpDelivery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The code the parcel recipient tells the provider at hand-over. Only the HMAC of the code is stored.
 *
 * @property int $id
 * @property int $booking_id
 * @property string $code_hash
 * @property int $attempts
 * @property int $max_attempts
 * @property int $resend_count
 * @property RecipientOtpDelivery $delivery_status
 * @property string|null $delivery_driver
 * @property Carbon|null $sent_at
 * @property Carbon|null $resend_available_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $locked_at
 */
class BookingRecipientOtp extends Model
{
    protected $fillable = [
        'booking_id', 'code_hash', 'attempts', 'max_attempts', 'resend_count', 'delivery_status', 'delivery_driver',
        'sent_at', 'resend_available_at', 'verified_at', 'locked_at',
    ];

    protected $hidden = ['code_hash'];

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'delivery_status' => RecipientOtpDelivery::class,
            'sent_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'verified_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }
}
