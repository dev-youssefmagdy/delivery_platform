<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Customer-only profile data (SRS §6.2 step 6). Marketing opt-in is stored apart from the terms consent.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $accessibility_needs
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property bool $marketing_opt_in
 * @property Carbon|null $marketing_opt_in_at
 * @property-read User $user
 */
class CustomerProfile extends Model
{
    protected $fillable = [
        'user_id', 'accessibility_needs', 'emergency_contact_name', 'emergency_contact_phone', 'marketing_opt_in', 'marketing_opt_in_at',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marketing_opt_in' => 'boolean',
            'marketing_opt_in_at' => 'datetime',
        ];
    }
}
