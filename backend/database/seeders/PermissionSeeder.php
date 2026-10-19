<?php

namespace Database\Seeders;

use App\Enums\Admin\AdminPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Syncs the AdminPermission enum into Spatie permissions on the `admin` guard. Idempotent: existing rows are
 * kept, missing ones are created, and permissions that no longer exist in the enum are removed.
 */
class PermissionSeeder extends Seeder
{
    public const GUARD = 'admin';

    public function run(): void
    {
        $names = AdminPermission::values();

        foreach ($names as $name) {
            Permission::findOrCreate((string) $name, self::GUARD);
        }

        Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereNotIn('name', $names)
            ->get()
            ->each(static fn (Permission $permission) => $permission->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
