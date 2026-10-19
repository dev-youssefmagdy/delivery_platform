<?php

namespace Database\Seeders;

use App\Enums\Admin\AdminRole;
use App\Enums\Common\Locale;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the 12 SRS system roles with their Appendix B default permissions. Idempotent and non-destructive:
 * a role that already exists keeps the permissions an admin edited in the panel (only its system flag and
 * missing labels are restored).
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        foreach (AdminRole::cases() as $case) {
            $role = Role::query()->where('guard_name', PermissionSeeder::GUARD)->where('name', $case->value)->first();
            $created = $role === null;

            $role ??= new Role(['name' => $case->value, 'guard_name' => PermissionSeeder::GUARD]);
            $role->is_system = true;

            foreach (Locale::cases() as $locale) {
                if ($created || $role->getTranslation('label', $locale->value, false) === '') {
                    $role->setTranslation('label', $locale->value, (string) __("enums.admin_role.{$case->value}", [], $locale->value));
                }
            }

            $role->save();

            if ($created) {
                $role->syncPermissions(array_map(static fn ($permission): string => $permission->value, $case->defaultPermissions()));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
