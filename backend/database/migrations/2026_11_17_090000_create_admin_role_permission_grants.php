<?php

use App\Enums\Admin\AdminRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Ledger of the default permissions already offered to each system role (P6.1). `admin:sync-role-permissions` grants a default
| permission only when the ledger has no row for (role, permission), so a permission an administrator removed from a role in the panel
| is never granted again. Roles that already exist are backfilled: every default permission counts as offered EXCEPT the ones added
| to the enum after the first role seeding (P1.3 to P5.7), which stored roles may be missing.
*/
return new class extends Migration
{
    /** @var list<string> */
    private const ADDED_AFTER_FIRST_SEEDING = [
        'leads.manage', 'referrals.view', 'referrals.manage', 'referrals.review', 'loyalty.view', 'loyalty.manage',
        'incentives.view', 'incentives.manage', 'incentives.review', 'reports.export_pii', 'otp_challenges.view',
    ];

    public function up(): void
    {
        Schema::create('admin_role_permission_grants', function (Blueprint $table): void {
            $table->id();
            $table->string('role', 64);
            $table->string('permission', 96);
            $table->timestamp('offered_at')->useCurrent();

            $table->unique(['role', 'permission']);
        });

        $existing = DB::table('roles')->where('guard_name', 'admin')->pluck('name')->all();
        $rows = [];

        foreach (AdminRole::cases() as $case) {
            if (! in_array($case->value, $existing, true)) {
                continue;
            }

            foreach ($case->defaultPermissions() as $permission) {
                if (! in_array($permission->value, self::ADDED_AFTER_FIRST_SEEDING, true)) {
                    $rows[] = ['role' => $case->value, 'permission' => $permission->value, 'offered_at' => now()];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('admin_role_permission_grants')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role_permission_grants');
    }
};
