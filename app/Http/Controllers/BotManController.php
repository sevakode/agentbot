<?php

namespace App\Http\Controllers;

use App\Http\Conversations\CreateBotConversation;
use BotMan\BotMan\BotMan;
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
    }

    public function handleDynamicBot($token)
    {
        $bot = Bot::where('token', $token)->first();
    
        if ($bot) {
            $botman = app('botman');
            $botman->middleware->received(new BotAuthenticationMiddleware($bot));
    
            $botConfig = [
                'telegram' => [
                    'token' => $token
                ],
                // Другие настройки бота
            ];
    
            $botman = app('botman', [$botConfig]);
            $botman->listen();
        } else {
            return response()->json(['error' => 'Invalid bot token'], 404);
        }
    }
    
}