<?php

namespace App\Http\Middleware\BotMan;

use App\Models\User;
use App\Models\Bot;
use App\Services\AgentHubApiService;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Received;

class BotAuthenticationMiddleware implements Received
{
    protected $agentHubApiService;

    public function __construct(AgentHubApiService $agentHubApiService)
    {
        $this->agentHubApiService = $agentHubApiService;
    }

    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $userId = $message->getSender();
        $userInput = $message->getText();

        $currentBot = $this->getCurrentBot($bot);

        if (!$currentBot) {
            $bot->reply("Извините, произошла ошибка. Попробуйте позже.");
            return;
        }

        if ($this->requiresAuthentication($currentBot)) {
            if (!$this->checkAuthenticationCode($currentBot, $userInput)) {
                $this->requestAuthentication($bot);
                return;
            }
        }

        $user = $this->findOrCreateUser($userId);

        // Передаем данные пользователя и бота в контроллер через атрибуты сообщения
        $message->addExtras('user', $user);
        $message->addExtras('bot', $currentBot);

        return $next($message);
    }

    protected function findOrCreateUser($userId)
    {

        $user = User::where('messenger_id', $userId)->first();


        if (!$user) {

            $userData = [
                'username' => $userId,
                'name' => 'default_name',
                'surname' => 'default_surname',
                'email' => $userId . '@example.com',
                'is_admin' => false,
                'avatar' => null,
                'description' => null,
                'is_operator' => false,
                'password' => str_random(8),
            ];

            $agentHubUser = $this->agentHubApiService->registerUser($userData);

            $user = User::create([
                'messenger_id' => $userId,
                'agent_hub_id' => $agentHubUser['id'],
                'name' => $agentHubUser['name'],
                'email' => $agentHubUser['email'],
                'password' => bcrypt($userData['password']),
            ]);
        }

        return $user;
    }

    protected function requiresAuthentication($bot)
    {
        // Проверяем, активен ли код аутентификации для бота
        return !empty($bot->auth_code) && now()->lessThan($bot->auth_code_expires_at);
    }

    protected function checkAuthenticationCode($bot, $userInput)
    {
        // Проверяем, совпадает ли введенный код с тем, что хранится в БД
        return $bot->auth_code === $userInput;
    }

    protected function requestAuthentication(BotMan $bot)
    {
        $bot->reply("Для доступа к этому боту требуется код авторизации. Пожалуйста, введите его.");
    }

    protected function getCurrentBot($bot)
    {
        // Ваша логика для определения текущего бота
        // Например, вы можете извлечь token из $bot и найти соответствующий Bot объект в БД
        return Bot::first(); // Примерный код, замените на реальный запрос
    }
}
