<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Bot;
use App\Services\AgentHubApiService;

class CreateGroupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agentbot:make:group';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected AgentHubApiService $service;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->service = app(AgentHubApiService::class);
        \DB::transaction(function() {
            $name = $this->ask('Enter group name:');
            $description = $this->ask('Enter group description:', '');
            $prompt = $this->ask('Enter group prompt:', '');
            $userIdOrUsername = $this->ask('Enter user id or username:');

            $user = User::where('id', $userIdOrUsername)
                        ->orWhere('username', $userIdOrUsername)
                        ->first();
            if (!$user) {
                $user = $this->createUser();
                if (is_null($user)) return;
            }

            $bot = new Bot();
            $bot->user_id = $user->id;
            $bot->token = $this->ask('Enter bot token:', $bot->token);

            $groupId = $this->service->createGroup($name, $description, $prompt)->json()['id'];
            $bot->group_id = $groupId;
            $bot->save();

            $this->info('Group created successfully!');
            $this->info("User:\n". print_r($user->toArray(), 1));
            $this->info("Bot:\n". print_r($bot->toArray(), 1));
        });
    }


    public function createUser()
    {
        $this->error('User not found!');
        $isCreate = $this->confirm('Create a user? (y)', true);

        if (! $isCreate) return null;

        $username = $this->ask('Enter service username:', str(fake()->firstName())->limit(8, ''));
        $password = $this->ask('Enter service password:', 'test_password');
        $serviceUserResponse = $this->service->createUser(
            username: $username,
            password: $password,
            name: $this->ask('Enter firstname:', fake()->firstName()),
            surname: $this->ask('Enter lastname:', fake()->lastName()),
            email: $this->ask('Enter email:', fake()->email()),
        );

        if ($serviceUserResponse->getStatusCode() !== 200) 
            dd($serviceUserResponse, $serviceUserResponse->json());
        $this->info("Service User Success!");

        $user = User::create([
            'username' => $this->ask('Enter telegram username:', $username),
            'password' => $this->ask('Enter password:', $password),
            'driver' => 'telegram',
            'chat_id' => $serviceUserResponse->collect()->get('id')
        ]);
        $this->info("User Success!");

        return $user;
    }
}
