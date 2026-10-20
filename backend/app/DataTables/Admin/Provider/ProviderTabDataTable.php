<?php

namespace App\DataTables\Admin\Provider;

use App\DataTables\Admin\AdminDataTable;
use App\Models\ProviderProfile;
use LogicException;

/**
 * Base of the DataTables inside the provider show page tabs. The provider comes from the `{provider}` route parameter
 * (already authorized by the route's `can:` middleware); global search is off.
 */
abstract class ProviderTabDataTable extends AdminDataTable
{
    protected bool $globalSearch = false;

    protected function provider(): ProviderProfile
    {
        $provider = $this->request()->route('provider');

        return $provider instanceof ProviderProfile ? $provider : throw new LogicException('Provider tab tables need a {provider} route parameter.');
    }
}
