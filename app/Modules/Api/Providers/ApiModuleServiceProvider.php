<?php

namespace App\Modules\Api\Providers;

use Illuminate\Support\ServiceProvider;

class ApiModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('app/Modules/Api/Database/Migrations'));
    }
}
