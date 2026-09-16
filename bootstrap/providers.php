<?php

use App\Modules\Api\Providers\ApiModuleServiceProvider;
use App\Modules\Web\Providers\WebModuleServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    WebModuleServiceProvider::class,
    ApiModuleServiceProvider::class,
];
