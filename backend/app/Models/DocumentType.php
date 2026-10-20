<?php

namespace App\Models;

use App\Enums\Provider\DocumentSubject;
use App\Support\Audit\Auditable;
use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * A kind of document providers or vehicles must upload (driving licence, vehicle registration ...). What is
 * mandatory where is decided by its `DocumentRequirement` rows, never by code (SRS §7.1: requirements are configurable).
 *
 * @property int $id
 * @property string $code
 * @property string $name translated in the current locale; raw JSON {ar, en}
 * @property string|null $description translated in the current locale; raw JSON {ar, en}
 * @property DocumentSubject $applies_to
 * @property bool $requires_expiry
 * @property bool $requires_number
 * @property list<string> $allowed_mimes
 * @property int $max_size_kb
 * @property int $sort_order
 * @property bool $active
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DocumentType extends Model
{
    use Auditable;

    /** @use HasFactory<DocumentTypeFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'code', 'name', 'description', 'applies_to', 'requires_expiry', 'requires_number', 'allowed_mimes', 'max_size_kb',
        'sort_order', 'active', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'requires_expiry' => false,
        'requires_number' => false,
        'max_size_kb' => 5120,
        'sort_order' => 0,
        'active' => true,
        'lock_version' => 0,
    ];

    /**
     * @return HasMany<DocumentRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applies_to' => DocumentSubject::class,
            'requires_expiry' => 'boolean',
            'requires_number' => 'boolean',
            'allowed_mimes' => 'array',
            'max_size_kb' => 'integer',
            'sort_order' => 'integer',
            'active' => 'boolean',
            'lock_version' => 'integer',
        ];
    }
}
