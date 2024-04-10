<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AgentBotController extends Controller
{
    public function handleAgentBot(Request $request, $token, $agent)
    {
        DriverManager::loadDriver(TelegramDriver::class);
        $botman = BotManFactory::create(['telegram' => $token]);
        $logMessage = json_encode($request->all(), JSON_PRETTY_PRINT);
        $botman->reply("Logs of the incoming request:\n```\n$logMessage\n```");

        // $agentConfig = config("agents.agent{$agent_id}");
        $agentConfig = [
            "urlStart"=>"https://hooks.zapier.com/hooks/catch/9924015/e315a7ba13844659803548c962baf52e/",
            "urlReply"=>"https://hooks.zapier.com/hooks/catch/9924015/703fb766fab94b668071fe757cc6a791/"
        ];

        $botman->hears('/start', function ($botman) use ($agentConfig) {
            $this->handleStartCommand($botman, $agentConfig);
        });

        $botman->hears('', function ($botman) use ($agentConfig) {
            $message = $botman->getMessage();
            if ($message->getPayload()['reply_to_message']['from']['id'] == $botman->getUser()->getId()) {
                $this->handleReply($message->getText(), $agentConfig);
            }
        });

        $botman->listen();
    }

    protected function handleStartCommand($botman, $agentConfig)
    {
        $messages = $botman->getMessages();
        $messages = collect($messages)->slice(-20);
        $this->sendMessagesToUrl($messages, $agentConfig['urlStart']);
    }

    protected function handleReply($replyText, $agentConfig)
    {
        $this->sendReplyToUrl($replyText, $agentConfig['urlReply']);
    }

    protected function sendMessagesToUrl($messages, $url)
    {
        Http::post($url, ['messages' => $messages->toArray()]);
    }

    protected function sendReplyToUrl($replyText, $url)
    {
        Http::post($url, ['reply' => $replyText]);
    }
}