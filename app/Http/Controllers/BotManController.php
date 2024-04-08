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
use App\Models\User;

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

    public function handleDynamicBot(Request $request, $token, AgentHubApiService $service)
    {
        DriverManager::loadDriver(TelegramDriver::class);
        /** @var BotMan */
        $botman = BotManFactory::create(['telegram' => compact('token')]);
        $bot = Bot::where('token', $token)->firstOrFail();
        
        $botman->hears('{message}', function ($botman, $message) use($service, $bot, $token) {
            $messengerId = $botman->getUser()->getId();
            $username = $botman->getUser()->getUsername();
            $user = User::query()->where('messenger_id', $messengerId)->first();
            $botman->reply('dasdasdad');
            if (is_null($user)) {
                $password = 'test_password';
                $responseUser = $service->createUser(
                    username: 'User' . str_replace(0, '', (string) $messengerId),
                    password: $password,
                    name: $botman->getUser()->getFirstName(),
                    surname: $botman->getUser()->getLastName() ?: '',
                    email: "$messengerId@bot.com",
                );
                $botman->reply($responseUser->json());

                logs()->info(print_r($responseUser->json(), 1));
                $responseChat = $service->createChat(
                    name: "Telegram - @$username",
                    groupId: $bot->group_id,
                    ownerId: $responseUser->json()['id'],
                );
                $botman->reply($responseUser->json());
                logs()->info(print_r($responseChat->json(), 1));
                $user = User::create([
                    'username' => $username,
                    'password' => $password,
                    'driver' => 'telegram',
                    'chat_id' => $responseChat->json()['id'],
                    'messenger_id' => $messengerId
                ]);
            }

            $callbackUrl = route('webhook.botman.answer', [
                'token' => $token,
                'messenger_id' => $user->messenger_id,
            ]);
            $username = 'User' . str_replace(0, '', (string) $user->messenger_id);
            $password = 'test_password';
            $service = new AgentHubApiService($username, $password);
            $response = $service->sendMessage($user->chat_id, $message, $callbackUrl);
            logs()->info([$user->chat_id, $message, $callbackUrl]);
            logs()->info($response->json());
            $botman->reply($response->json());

        });
        $botman->listen();

        return true;
    }

    public function handleAnswerBot(Request $request, $token, $messengerId)
    {
        logs()->info($request->all());
        $message = $request->content;
        $bot = Bot::where('token', $token)->firstOrFail();
        $config = ['telegram' => compact('token')];

        $botman = BotManFactory::create($config);
        $botman->say($message, $messengerId, TelegramDriver::class);

        return true;
    }
    
}