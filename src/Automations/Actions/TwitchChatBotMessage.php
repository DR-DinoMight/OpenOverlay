<?php

namespace Redbeed\OpenOverlay\Automations\Actions;

use Illuminate\Support\Facades\Artisan;
use Redbeed\OpenOverlay\Console\Commands\ChatBot\SendMessageCommand;
use Redbeed\OpenOverlay\Models\User\Connection;

class TwitchChatBotMessage
{
    use UseVariables;
    use UseTwitchChatMessage;

    private Connection $connection;
    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        Artisan::queue(SendMessageCommand::class, [
            'userId'  => $this->getUser()->id,
            '--botId' => $this->getBot()->id,
            'message' => $this->replaceInString($this->message),
        ]);
    }
}
