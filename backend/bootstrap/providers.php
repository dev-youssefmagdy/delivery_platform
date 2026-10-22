<?php

use App\Providers\AppServiceProvider;
use App\Providers\IntegrationServiceProvider;
use App\Providers\NotificationServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
    IntegrationServiceProvider::class,
    NotificationServiceProvider::class,
];
