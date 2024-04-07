<?php

namespace App\Http\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use App\Models\Bot;

class CreateBotConversation extends Conversation
{
    protected $name;
    protected $token;
    protected $files;
    protected $textPrompt;

    public function askName()
    {
        $this->ask('Какое имя у нового бота?', function($answer) {
            $this->name = $answer->getText();
            $this->askToken();
        });
    }

    public function askToken()
    {
        $this->ask('Введите токен для бота:', function($answer) {
            $this->token = $answer->getText();
            $this->askFiles();
        });
    }

    public function run()
    {
        // Запуск диалога с запросом имени бота
        $this->askName();
    }
}
