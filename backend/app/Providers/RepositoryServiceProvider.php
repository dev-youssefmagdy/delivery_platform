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
        // @admin-module:bindings
    ];
}
