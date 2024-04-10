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
            "urlStart" => "https://hooks.zapier.com/hooks/catch/9924015/e315a7ba13844659803548c962baf52e/",
            "urlReply" => "https://hooks.zapier.com/hooks/catch/9924015/703fb766fab94b668071fe757cc6a791/"
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

        // Отправка диалога на URL для команды /start
        $this->sendDialogToUrl($dialog, $agentConfig['urlStart']);

        // Очистка кеша после отправки
        Cache::forget("dialog_{$chatId}");
    }

    protected function handleMessage($botman, $agentConfig)
    {
        $message = $botman->getMessage();
        $chatId = $message->getPayload()['chat']['id'];
        $messageText = $message->getText();
        
        // // Получение имени, фамилии и юзернейма пользователя
        // $firstName = $botman->getUser()->getFirstName();
        // $lastName = $botman->getUser()->getLastName() ?: '';
        // $username = $botman->getUser()->getUsername();
        
        // // Объединение имени, фамилии и юзернейма в одну строку
        // $userInfo = $firstName . ' ' . $lastName;
        // if ($username) {
        //     $userInfo .= ' (@' . $username . ')';
        // }
    
        // Сохранение сообщения в кеш, если это не команда /start
        if ($messageText !== '/start') {
            $dialog = Cache::get("dialog_{$chatId}", []);
            $dialog[] = ['role' => 'user', 'content' => $messageText, ];
            $dialog = array_slice($dialog, -50); // Сохранение только последних 50 сообщений
            Cache::put("dialog_{$chatId}", $dialog);
        }
    
        // Проверка, является ли сообщение ответом пользователя (цитатой)
        $payload = $message->getPayload();
        if (isset($payload['reply_to_message']) && $payload['reply_to_message']['from']['id'] == $message->getBot()->getId()) {
            // Отправка диалога на URL для реплики
            $this->sendDialogToUrl($dialog, $agentConfig['urlReply']);
    
            // Очистка кеша после отправки ответа на реплику
            Cache::forget("dialog_{$chatId}");
        }
    }

    protected function sendDialogToUrl($dialog, $url)
    {
        Http::post($url, ['dialog' => $dialog]);
    }
}