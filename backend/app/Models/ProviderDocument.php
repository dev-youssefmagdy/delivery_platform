<?php

namespace App\Models;

use Database\Factories\ProviderDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document about the provider as a person or business (national ID, driving licence, company papers ...).
 *
 * @property int $provider_profile_id
 * @property-read ProviderProfile $providerProfile
 */
class ProviderDocument extends OwnedDocument
{
    /** @use HasFactory<ProviderDocumentFactory> */
    use HasFactory;

    protected $table = 'provider_documents';

    public function ownerProfile(): ProviderProfile
    {
        return $this->providerProfile;
    }

    public function auditTarget(): Model
    {
        return $this->providerProfile;
    }

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
