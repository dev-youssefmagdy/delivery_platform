<?php

namespace App\Support\Admin\Lookups;

use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Allowlisted lookups (config('admin.lookups')). Unknown keys are a 404, never a dynamic class resolution.
 */
final class LookupRegistry
{
    public function __construct(private readonly Container $container) {}

    public function get(string $key): Lookup
    {
        /** @var array<string, class-string<Lookup>> $lookups */
        $lookups = (array) config('admin.lookups', []);

        if (! isset($lookups[$key])) {
            throw new NotFoundHttpException;
        }

        $lookup = $this->container->make($lookups[$key]);

        if (! $lookup instanceof Lookup) {
            throw new NotFoundHttpException;
        }

        return $lookup;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, (array) config('admin.lookups', []));
    }
}
