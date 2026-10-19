<?php

namespace App\Console\Commands;

use App\Enums\Admin\AdminRole;
use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\Role;
use App\Services\Audit\AuditLogger;
use App\Support\Auth\AdminPassword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates an admin from the server shell (the only way to create the very first one: there are no default
 * credentials). The password is always entered interactively (never an argument, so it stays out of shell
 * history and process lists) and must satisfy the admin password policy.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
        {--name= : Full name (asked when omitted)}
        {--email= : Email address (asked when omitted)}
        {--role=super_admin : Role to assign (a roles.name value)}';

    protected $description = 'Create an admin interactively (no default credentials; run PermissionSeeder/RoleSeeder first)';

    public function handle(AuditLogger $audit): int
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('admin:create is interactive only: the password is never accepted as an argument.');

            return self::FAILURE;
        }

        $roleName = (string) $this->option('role');
        $role = Role::query()->where('guard_name', 'admin')->where('name', $roleName)->first();

        if ($role === null) {
            $this->components->error("Role [{$roleName}] does not exist. Run `php artisan db:seed --class=RoleSeeder` first. System roles: ".implode(', ', AdminRole::values()));

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: text('Full name', required: true, validate: ['max:191']));
        $email = mb_strtolower((string) ($this->option('email') ?: text('Email address', required: true, validate: ['email:rfc', 'max:191'])));

        if (Admin::query()->withTrashed()->where('email', $email)->exists()) {
            $this->components->error("An admin with the email [{$email}] already exists.");

            return self::FAILURE;
        }

        $plain = password('Password', required: true, validate: fn (string $value): ?string => $this->passwordError($value));
        $confirmation = password('Confirm password', required: true);

        if ($plain !== $confirmation) {
            $this->components->error('The passwords do not match.');

            return self::FAILURE;
        }

        $admin = Admin::query()->create(['name' => $name, 'email' => $email, 'password' => $plain, 'locale' => 'ar']);
        $admin->assignRole($role);

        $audit->record(AuditAction::Created, $admin, null, after: $admin->auditSnapshot(), meta: ['via' => 'artisan admin:create']);

        $this->components->info("Admin [{$email}] created with role [{$roleName}]. Sign in at /".config('admin.path').'/login'.($roleName === AdminRole::SuperAdmin->value ? ' (MFA enrolment is required at first sign-in).' : '.'));

        return self::SUCCESS;
    }

    private function passwordError(string $value): ?string
    {
        $validator = Validator::make(['password' => $value], ['password' => [AdminPassword::rule()]]);

        return $validator->fails() ? $validator->errors()->first('password') : null;
    }
}
