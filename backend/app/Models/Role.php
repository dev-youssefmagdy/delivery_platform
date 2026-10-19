<?php

namespace App\Models;

use App\Enums\Admin\AdminRole;
use App\Support\Audit\Auditable;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Translatable\HasTranslations;

/**
 * Admin role (Spatie, guard `admin`). `name` is the stable slug; `label` is the translated display name.
 * System roles (the 12 SRS roles) cannot be deleted or renamed.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property string $label translated in the current locale; raw JSON {ar, en}
 * @property bool $is_system
 * @property int $lock_version
 */
class Role extends SpatieRole
{
    use Auditable;
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['label'];

    protected $fillable = ['name', 'guard_name', 'label', 'is_system', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'guard_name' => 'admin',
        'is_system' => false,
        'lock_version' => 0,
    ];

    public function isSuperAdmin(): bool
    {
        return $this->name === AdminRole::SuperAdmin->value;
    }

    /**
     * Display name (translated label, falling back to the slug).
     */
    public function displayName(): string
    {
        $label = $this->getTranslation('label', app()->getLocale(), true);

        return $label !== '' ? $label : $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->getTranslations('label'),
            'is_system' => $this->is_system,
            'permissions' => $this->permissions()->pluck('name')->sort()->values()->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'lock_version' => 'integer',
        ];
    }
}
