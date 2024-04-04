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

    public function askFiles()
    {
        $this->ask('Пожалуйста, загрузите файлы или отправьте "пропустить", если файлы не требуются.', function($answer) {
            // Проверяем, является ли ответ файлом
            if ($answer->getMessage()->getAttachments()) {
                // Обрабатываем полученные файлы
                foreach ($answer->getMessage()->getAttachments() as $attachment) {
                    //@ToDo
                    // Делаем что-то с файлом, например, сохраняем ссылку на файл
                }
                $this->say('Файлы получены.');
                $this->askTextPrompt();
            } else {
                // Проверяем, ответил ли пользователь "пропустить"
                if ($answer->getText() === 'пропустить') {
                    $this->say('Загрузка файлов пропущена.');
                    $this->askTextPrompt();
                } else {
                    $this->repeat('Извините, я ожидал файлы. Пожалуйста, загрузите файлы или отправьте "пропустить".');
                }
            }
        });
    }
    

    public function askTextPrompt()
    {
        $this->ask('Введите текстовый промпт для бота:', function($answer) {
            $this->textPrompt = $answer->getText();
            $this->createBot();
        });
    }

    public function createBot()
    {
        //@todo
        // Здесь логика создания бота с использованием собранных данных
        Bot::create([
            'name' => $this->name,
            'token' => $this->token,
            // Другие поля
        ]);
        $this->say("Бот {$this->name} успешно создан!");
    }

    public function run()
    {
        // Запуск диалога с запросом имени бота
        $this->askName();
    }
}
