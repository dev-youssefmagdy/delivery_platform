<?php

namespace App\Providers;

use App\Repositories\Contracts\Admin\AdminExportRepositoryInterface;
use App\Repositories\Contracts\Admin\AdminLoginEventRepositoryInterface;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Repositories\Contracts\Admin\AdminSessionRepositoryInterface;
use App\Repositories\Contracts\Admin\RoleRepositoryInterface;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Repositories\Contracts\Common\IdempotencyKeyRepositoryInterface;
use App\Repositories\Contracts\Demo\DemoItemRepositoryInterface;
use App\Repositories\Contracts\Identity\AccessTokenRepositoryInterface;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;
use App\Repositories\Contracts\Identity\OtpChallengeRepositoryInterface;
use App\Repositories\Contracts\Identity\UserConsentRepositoryInterface;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use App\Repositories\Contracts\Policy\PolicyVersionRepositoryInterface;
use App\Repositories\Contracts\Settings\SettingsRepositoryInterface;
use App\Repositories\Eloquent\Admin\AdminExportRepository;
use App\Repositories\Eloquent\Admin\AdminLoginEventRepository;
use App\Repositories\Eloquent\Admin\AdminRepository;
use App\Repositories\Eloquent\Admin\AdminSessionRepository;
use App\Repositories\Eloquent\Admin\RoleRepository;
use App\Repositories\Eloquent\Audit\AuditLogRepository;
use App\Repositories\Eloquent\Common\CountryRepository;
use App\Repositories\Eloquent\Common\CurrencyRepository;
use App\Repositories\Eloquent\Common\IdempotencyKeyRepository;
use App\Repositories\Eloquent\Demo\DemoItemRepository;
use App\Repositories\Eloquent\Identity\AccessTokenRepository;
use App\Repositories\Eloquent\Identity\AccountDeletionRequestRepository;
use App\Repositories\Eloquent\Identity\CustomerProfileRepository;
use App\Repositories\Eloquent\Identity\OtpChallengeRepository;
use App\Repositories\Eloquent\Identity\UserConsentRepository;
use App\Repositories\Eloquent\Identity\UserDeviceRepository;
use App\Repositories\Eloquent\Identity\UserRepository;
use App\Repositories\Eloquent\Policy\PolicyVersionRepository;
use App\Repositories\Eloquent\Settings\SettingsRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository interface → Eloquent implementation map. `make:admin-module` (P0.3) appends to $bindings.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AdminExportRepositoryInterface::class => AdminExportRepository::class,
        AdminLoginEventRepositoryInterface::class => AdminLoginEventRepository::class,
        AdminRepositoryInterface::class => AdminRepository::class,
        AdminSessionRepositoryInterface::class => AdminSessionRepository::class,
        RoleRepositoryInterface::class => RoleRepository::class,
        AuditLogRepositoryInterface::class => AuditLogRepository::class,
        CountryRepositoryInterface::class => CountryRepository::class,
        CurrencyRepositoryInterface::class => CurrencyRepository::class,
        IdempotencyKeyRepositoryInterface::class => IdempotencyKeyRepository::class,
        DemoItemRepositoryInterface::class => DemoItemRepository::class,
        SettingsRepositoryInterface::class => SettingsRepository::class,
        PolicyVersionRepositoryInterface::class => PolicyVersionRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        CustomerProfileRepositoryInterface::class => CustomerProfileRepository::class,
        OtpChallengeRepositoryInterface::class => OtpChallengeRepository::class,
        UserDeviceRepositoryInterface::class => UserDeviceRepository::class,
        UserConsentRepositoryInterface::class => UserConsentRepository::class,
        AccountDeletionRequestRepositoryInterface::class => AccountDeletionRequestRepository::class,
        AccessTokenRepositoryInterface::class => AccessTokenRepository::class,
        // @admin-module:bindings
    ];
}
