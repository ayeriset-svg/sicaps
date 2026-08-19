<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Paginator bertema (warna brand, bukan hitam).
        Paginator::defaultView('vendor.pagination.capstone');
        Paginator::defaultSimpleView('vendor.pagination.capstone');
    }
}
