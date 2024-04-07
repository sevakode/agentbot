<?php

namespace App\Http\Controllers;

use App\Http\Conversations\CreateBotConversation;
use App\Services\AgentHubApiService;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use Illuminate\Http\Request;
use App\Http\Conversations\ExampleConversation;
use App\Http\Middleware\BotMan\BotAuthenticationMiddleware;
use App\Models\Bot;

class BotManController extends Controller
{
    public function handleMainBot()
    {
        $botman = app('botman');
        $botman->hears('create_bot', function (BotMan $bot) {
            $bot->startConversation(new CreateBotConversation());
        });
        $botman->listen();

        return true;
    }

    public function handleDynamicBot($token, AgentHubApiService $service)
    {
        $bot = Bot::where('token', $token)->firstOrFail();
        $config = ['telegram' => compact('token')];

        DriverManager::loadDriver(TelegramDriver::class);
        $botman = BotManFactory::create($config);
        $botman->hears('.*', function ($bot, $message) use($service, $botman, $token) {
            $chatId = $botman->getUser()->getInfo()['chat_id'];
            $callbackUrl = route('webhook.botman.answer', [
                'token' => $token,
                'chat_id' => $chatId,
            ]);
            $service->sendMessage($chatId, $message, $callbackUrl);
        });
        $botman->listen();

        return true;
    }

    public function handleAnswerBot(Request $request, $token, $chatId)
    {
        $message = $request->content;
        $bot = Bot::where('token', $token)->firstOrFail();
        $config = ['telegram' => compact('token')];

        $botman = BotManFactory::create($config);
        $botman->say($message, $chatId, TelegramDriver::class);

        return true;
    }
    
}