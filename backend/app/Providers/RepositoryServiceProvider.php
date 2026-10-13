<?php

namespace App\Providers;

use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Repositories\Contracts\Common\IdempotencyKeyRepositoryInterface;
use App\Repositories\Eloquent\Audit\AuditLogRepository;
use App\Repositories\Eloquent\Common\CountryRepository;
use App\Repositories\Eloquent\Common\CurrencyRepository;
use App\Repositories\Eloquent\Common\IdempotencyKeyRepository;
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
        AuditLogRepositoryInterface::class => AuditLogRepository::class,
        CountryRepositoryInterface::class => CountryRepository::class,
        CurrencyRepositoryInterface::class => CurrencyRepository::class,
        IdempotencyKeyRepositoryInterface::class => IdempotencyKeyRepository::class,
    ];
}
