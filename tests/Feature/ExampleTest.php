<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AgentHubApiService;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public AgentHubApiService $service;
    public function __construct(string $name)
    {
        parent::__construct($name);
    }
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response()
    {
        $this->service = $this->app->make(AgentHubApiService::class);
        
        dd($this->service->getUsers());

        $this->assertTrue(true, 'adasd');
    }
}
