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
        if ( ! defined('CURL_SSLVERSION_TLSv1_2')) { define('CURL_SSLVERSION_TLSv1_2', 6); }
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
