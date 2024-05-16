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
            $botman->typesAndWaits(10);

            $message = $botman->getMessage()?->getText() ?: $message;

            $messengerId = $botman->getUser()->getId();
            $username = $botman->getUser()->getUsername();
            $user = User::query()->where('messenger_id', $messengerId)->first();
            logs()->info($messengerId);
            logs()->info($user?->toArray());
            $usernameService = 'User' . str_replace(0, '', (string) $messengerId);
            $password = 'test_password';
            $meService = new AgentHubApiService($usernameService, $password);

            if (is_null($user)) {
                $responseUser = $service->createUser(
                    username: $usernameService,
                    password: $password,
                    name: $botman->getUser()->getFirstName(),
                    surname: $botman->getUser()->getLastName() ?: '',
                    email: "$messengerId@bot.com",
                );
                logs()->info(print_r($responseUser->json(), 1));
                logs()->info('--------------1----------------');
                if ($responseUser->getStatusCode() !== 200) {
                    $responseUser = $meService->meUser();

                    logs()->info(print_r($responseUser->json(), 1));
                    logs()->info('--------------2----------------');
                }
                $responseChat = $meService;
                $responseChatDataId = $meService->meChats()->collect('data')?->first()['id'] ?? false;
                if ($responseUser->getStatusCode() !== 200 || is_null($responseChatDataId)) {
                    $responseChat = $service->createChat(
                        name: "Telegram - @$username",
                        groupId: $bot->group_id,
                        ownerId: $responseUser->json()['id'],
                    );
                    logs()->info(print_r($responseChat->json(), 1));
                    $responseChatDataId = $responseChat->json()['id'];
                }
                logs()->info(print_r($responseChatDataId, 1));
                logs()->info('--------------3----------------');
                $user = User::create([
                    'username' => $username,
                    'password' => $password,
                    'driver' => 'telegram',
                    'chat_id' => $responseChatDataId,
                    'messenger_id' => $messengerId
                ]);
            }

            $callbackUrl = env("LOCAL_URL")."/api/botman/$token/chats/$user->messenger_id";
            $password = 'test_password';
            $response = $meService->sendMessage($user->chat_id, $message, $callbackUrl);
            logs()->info([$user->chat_id, $message, $callbackUrl]);
            logs()->info($response->json());
        });
        $botman->listen();

        return true;
    }

    public function handleAnswerBot(Request $request, $token, $messengerId)
    {
        logs()->info($request->all());
        $message = $request->response;
        logs()->info($message);
        logs()->info($token);
        $bot = Bot::where('token', $token)->firstOrFail();
        logs()->info($bot);
        $config = ['telegram' => compact('token')];

        $botman = BotManFactory::create($config);
        $botman->say($message, $messengerId, TelegramDriver::class, [
            'parse_mode' => 'HTML'
        ]);

        return true;
    }
    
}