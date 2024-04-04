<?php
namespace App\Http\Middleware\BotMan;

use App\Models\User;
use App\Models\Bot;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Received;

class BotAuthenticationMiddleware implements Received
{
    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $userId = $message->getSender(); // Получаем ID пользователя из сообщения
        $userInput = $message->getText(); // Текст сообщения пользователя, возможно содержит код аутентификации
        
        // Предполагается, что у вас есть способ получения текущего бота (например, из $bot или другого источника)
        $currentBot = $this->getCurrentBot($bot);
        
        if (!$currentBot) {
            // Если бот не найден, прекращаем обработку
            $bot->reply("Извините, произошла ошибка. Попробуйте позже.");
            return;
        }

        if ($this->requiresAuthentication($currentBot)) {
            if (!$this->checkAuthenticationCode($currentBot, $userInput)) {
                $this->requestAuthentication($bot);
                return; // Прекращаем обработку сообщения, если требуется аутентификация и код не подошел
            }
        }

        $user = $this->findOrCreateUser($userId);

        return $next($message);
    }

    protected function findOrCreateUser($userId)
    {
        $user = User::where('messenger_id', $userId)->first();

        if (!$user) {
            $user = User::create([
                'messenger_id' => $userId,
                'name' => 'default_name',
                // Другие поля пользователя
                'email' => $userId . '@example.com',
                'password' => bcrypt(str_random(8)), // Пример генерации пароля
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
