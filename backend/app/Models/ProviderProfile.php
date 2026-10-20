<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Provider profile stub; onboarding data (type, documents, status) is added by P2.1.
 *
 * @property int $id
 * @property int $user_id
 * @property-read User $user
 */
class ProviderProfile extends Model
{
    protected $fillable = ['user_id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
