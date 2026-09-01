<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Leave;
use App\Observers\LeaveObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {

        $this->app->singleton(HrAnnouncementService::class);
        
        // Only register Pail in development environment
        if ($this->app->environment('local', 'development')) {
            if (class_exists(\Laravel\Pail\PailServiceProvider::class)) {
                $this->app->register(\Laravel\Pail\PailServiceProvider::class);
            }
        }
    }

    public function boot()
    {
        // Register Leave Observer
        Leave::observe(LeaveObserver::class);
    }
}