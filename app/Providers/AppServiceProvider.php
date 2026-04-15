<?php

namespace App\Providers;

use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Work around older/limited MySQL index key length when using utf8mb4.
        // (e.g. unique index on VARCHAR(255) may exceed max key length.)
        SchemaBuilder::defaultStringLength(191);
    }
}
