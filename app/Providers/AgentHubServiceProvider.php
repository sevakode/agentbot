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
        $this->app->bind(AgentHubApiService::class, function ($app) {
            $log = config('services.agenthub.login');
            $pass = config('services.agenthub.password');
            return new AgentHubApiService($log, $pass);
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
