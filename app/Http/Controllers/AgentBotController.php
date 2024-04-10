<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AgentBotController extends Controller
{
    public function handleAgentBot(Request $request, $token)
    {
        DriverManager::loadDriver(TelegramDriver::class);
        $botman = BotManFactory::create(['telegram' => $token]);

        // $agentConfig = config("agents.agent{$agent_id}");
        $agentConfig = [
            "urlStart" => "https://hooks.zapier.com/hooks/catch/9924015/e315a7ba13844659803548c962baf52e/",
            "urlReply" => "https://hooks.zapier.com/hooks/catch/9924015/703fb766fab94b668071fe757cc6a791/"
        ];

        $botman->hears('/start', function ($botman) use ($agentConfig, $token) {
            $this->handleStartCommand($botman, $agentConfig, $token);
        });

        $botman->hears('', function ($botman) use ($agentConfig) {
            $message = $botman->getMessage();
            $payload = $message->getPayload();
        
            if (isset($payload['reply_to_message']) && $payload['reply_to_message']['from']['id'] == $botman->getUser()->getId()) {
                $this->handleReply($message, $agentConfig);
            }
        });

        $botman->listen();
    }

    protected function handleStartCommand($botman, $agentConfig, $token)
    {
        $message = $botman->getMessage();
        $chatId = $message->getPayload()['chat']['id'];
        
        $response = Http::get("https://api.telegram.org/bot{$token}/getUpdates", [
            'chat_id' => $chatId,
            'limit' => 20,
        ]);
        
        if ($response->ok()) {
            $updates = $response->json()['result'];
            $messages = collect($updates)->pluck('message')->filter();
            
            $this->sendMessagesToUrl($messages, $agentConfig['urlStart']);
        }
    }
    

    protected function handleReply($message, $agentConfig)
    {
        $this->sendReplyToUrl($message, $agentConfig['urlReply']);
    }

    protected function sendMessagesToUrl($messages, $url)
    {
        Http::post($url, ['messages' => $messages->toArray()]);
    }

    protected function sendReplyToUrl($message, $url)
    {
        Http::post($url, ['reply' => $message->getText()]);
    }
}