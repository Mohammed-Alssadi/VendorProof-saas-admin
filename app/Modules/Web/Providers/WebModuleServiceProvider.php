<?php

namespace App\Modules\Web\Providers;

use Illuminate\Support\ServiceProvider;

class WebModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('app/Modules/Web/Database/Migrations'));
    }
}
