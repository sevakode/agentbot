<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AgentBotController extends Controller
{
    public function handleAgentBot(Request $request, $token)
    {
        DriverManager::loadDriver(TelegramDriver::class);
        $botman = BotManFactory::create(['telegram' => $token]);

        $agentConfig = [
            "url" => "https://hooks.zapier.com/hooks/catch/9924015/e315a7ba13844659803548c962baf52e/",
        ];

        $botman->hears('/start', function ($botman) use ($agentConfig) {
            $this->handleStartCommand($botman, $agentConfig);
        });

        $botman->hears('', function ($botman) use ($agentConfig) {
            $this->handleMessage($botman, $agentConfig);
        });

        $botman->listen();
    }

    protected function handleStartCommand($botman, $agentConfig)
    {
        $message = $botman->getMessage();
        $chatId = $message->getPayload()['chat']['id'];

        // Получение последних 50 сообщений из кеша
        $dialog = Cache::get("dialog_{$chatId}", []);

        // Добавление идентификатора группы (чата) в диалог
        $dialog['chatId'] = $chatId;

        // Отправка диалога на URL
        $this->sendDialogToUrl($dialog, $agentConfig['url']);

        // Очистка кеша после отправки
        Cache::forget("dialog_{$chatId}");
    }

    protected function handleMessage($botman, $agentConfig)
    {
        $message = $botman->getMessage();
        $chatId = $message->getPayload()['chat']['id'];
        $messageText = $message->getText();
        
        // Получение информации об отправителе сообщения из полезной нагрузки
        $sender = $message->getPayload()['from'];
        $firstName = $sender['first_name'];
        $lastName = $sender['last_name'] ?? '';
        $username = $sender['username'] ?? '';
        
        // Объединение имени, фамилии и юзернейма в одну строку
        $userInfo = $firstName . ' ' . $lastName;
        if ($username) {
            $userInfo .= ' (@' . $username . ')';
        }

        // Сохранение сообщения в кеш, если это не команда /start
        if ($messageText !== '/start') {
            $dialog = Cache::get("dialog_{$chatId}", []);
            $dialog['messages'][] = ['role' => 'user', 'content' => $messageText, 'userInfo' => $userInfo];
            $dialog['messages'] = array_slice($dialog['messages'], -50); // Сохранение только последних 50 сообщений
            Cache::put("dialog_{$chatId}", $dialog);
        }

        // Проверка, является ли сообщение ответом пользователя (цитатой)
        $payload = $message->getPayload();
        if (isset($payload['reply_to_message']) && $payload['reply_to_message']['from']['id'] == $botman->getBot()->getId()) {
            // Добавление идентификатора группы (чата) в диалог
            $dialog['chatId'] = $chatId;

            // Отправка диалога на URL
            $this->sendDialogToUrl($dialog, $agentConfig['url']);

            // Очистка кеша после отправки ответа на реплику
            Cache::forget("dialog_{$chatId}");
        }
    }

    protected function sendDialogToUrl($dialog, $url)
    {
        Http::post($url, ['dialog' => $dialog]);
    }
}