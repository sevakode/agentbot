<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AgentHubApiService;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use WithFaker;

    public AgentHubApiService $service;
    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    /**
     * A basic test example.
     */
    public function test_the_api_service_get_users_response()
    {
        $this->service = $this->app->make(AgentHubApiService::class);
        $response = $this->service->getUsers();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_the_api_service_create_and_delete_user_response()
    {
        $this->service = $this->app->make(AgentHubApiService::class);

        $username = str($this->faker->firstName())->limit(8, '');
        $response = $this->service->createUser(
            username: $username,
            password: 'test_password',
            name: $this->faker->firstName(),
            surname: $this->faker->lastName(),
            email: $this->faker->email(),
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->service->deleteUser(
            id: $response['id']
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}
