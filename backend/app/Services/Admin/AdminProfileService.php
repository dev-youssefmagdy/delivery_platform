<?php

namespace App\Services\Admin;

use App\DTOs\Admin\ProfileData;
use App\Enums\Common\AuditAction;
use App\Enums\Common\Locale;
use App\Models\Admin;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;

/**
 * An admin's own profile and language preference.
 */
final class AdminProfileService
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function update(Admin $admin, ProfileData $data): Admin
    {
        return $this->db->transaction(function () use ($admin, $data): Admin {
            $before = $admin->auditSnapshot();
            $this->admins->update($admin, [
                'name' => $data->name,
                'phone' => $data->phone,
                'locale' => $data->locale?->value,
            ]);

            $this->audit->recordChanges(AuditAction::Updated, $admin, $admin, $before, $admin->auditSnapshot());

            return $admin;
        });
    }

    /**
     * Persists the language chosen in the topbar switcher on the admin record.
     */
    public function updateLocale(Admin $admin, Locale $locale): void
    {
        if ($admin->locale !== $locale->value) {
            $this->admins->update($admin, ['locale' => $locale->value]);
        }
    }
}
