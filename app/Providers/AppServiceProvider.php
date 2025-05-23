<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Jobs\CancelExpiredBookings;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // CancelExpiredBookings::dispatch();
    }

    public function register()
    {
        //
    }
}