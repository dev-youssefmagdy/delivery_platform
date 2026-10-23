<?php

namespace App\Console\Commands;

use App\Enums\Admin\AdminRole;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * `admin:sync-role-permissions`: grants the default permissions that were added to the code after a system role was stored
 * (for example the growth permissions of P5.7). Safe to run on every deploy: a (role, permission) pair is offered once, tracked in
 * `admin_role_permission_grants`, so a permission an administrator removed in the panel is not granted again, and nothing is ever
 * revoked. Custom (non-system) roles are not touched. `--dry-run` only lists what would be granted.
 */
class SyncRolePermissions extends Command
{
    protected $signature = 'admin:sync-role-permissions {--dry-run : List the grants without writing}';

    protected $description = 'Grant newly added default permissions to the system roles without overwriting administrator edits';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if (! $dry) {
            (new PermissionSeeder)->run();
        }

        $granted = 0;

        foreach (AdminRole::cases() as $case) {
            $role = Role::query()->where('guard_name', PermissionSeeder::GUARD)->where('name', $case->value)->first();

            if ($role === null) {
                continue;
            }

            $offered = DB::table('admin_role_permission_grants')->where('role', $case->value)->pluck('permission')->all();
            $held = $role->permissions()->pluck('name')->all();

            foreach ($case->defaultPermissions() as $permission) {
                if (in_array($permission->value, $offered, true)) {
                    continue;
                }

                if (! in_array($permission->value, $held, true)) {
                    $this->line(sprintf('%s %s -> %s', $dry ? 'would grant' : 'granting', $permission->value, $case->value));
                    $granted++;

                    if (! $dry) {
                        $role->givePermissionTo($permission->value);
                    }
                }

                if (! $dry) {
                    DB::table('admin_role_permission_grants')->insertOrIgnore(['role' => $case->value, 'permission' => $permission->value, 'offered_at' => now()]);
                }
            }
        }

        if (! $dry) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $this->info(sprintf('%d permission grant(s)%s.', $granted, $dry ? ' pending' : ' applied'));

        return self::SUCCESS;
    }
}
