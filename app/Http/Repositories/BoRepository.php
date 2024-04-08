<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BoRepository
{
    public function __construct(
        protected AgentHubApiService $service,
        protected string $usernameService,
        protected string $username,
        protected string $password,
    )
    {}

    public function createAllForUser()
    {
        $responseUser = $this->firstOrCreateServiceUser();
        $responseChat = $this->firstOrCreateServiceChat();
    }

    public function firstOrCreateServiceUser()
    {
        $responseUser = $this->service->createUser(
            username: $usernameService,
            password: $password,
            name: $botman->getUser()->getFirstName(),
            surname: $botman->getUser()->getLastName() ?: '',
            email: "$messengerId@bot.com",
        );
        logs()->info(print_r($responseUser->json(), 1));
        logs()->info('--------------1----------------');
        if ($responseUser->getStatusCode() !== 200) {
            $responseUser = new AgentHubApiService($usernameService, $password);
            $responseUser = $responseUser->meUser();

            logs()->info(print_r($responseUser->json(), 1));
            logs()->info('--------------2----------------');
        }

        return $responseUser;
    }

    public function firstOrCreateServiceChat()
    {
        $responseChat = new AgentHubApiService($usernameService, $password);
        $responseChatData = $responseChat->meChats()->collect('data')?->first();
        if ($responseUser->getStatusCode() !== 200 || is_null($responseChatData?->get('id'))) {
            $responseChat = $service->createChat(
                name: "Telegram - @$username",
                groupId: $bot->group_id,
                ownerId: $responseUser->json(),
            );
            $responseChatData = $responseChat->json();
        }
    }
}