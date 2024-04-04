<?php

namespace App\Providers;

use App\Services\AgentHubApiService;
use Illuminate\Support\ServiceProvider;

class AgentHubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('AgentHubApiService', function ($app) {
            return new AgentHubApiService();
        });
        }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
