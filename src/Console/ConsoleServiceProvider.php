<?php

namespace Redbeed\OpenOverlay\Console;

use Illuminate\Support\ServiceProvider;
use Redbeed\OpenOverlay\Console\Commands\ChatBotCommand;
use Redbeed\OpenOverlay\Console\Commands\ChatBotMessageCommand;
use Redbeed\OpenOverlay\Console\Commands\EventBroadcastFaker;
use Redbeed\OpenOverlay\Console\Commands\EventSubDeleteCommand;
use Redbeed\OpenOverlay\Console\Commands\EventSubListingCommand;
use Redbeed\OpenOverlay\Console\Commands\Make\MakeBotCommandCommand;
use Redbeed\OpenOverlay\Console\Commands\Make\MakeBotSchedulingCommand;
use Redbeed\OpenOverlay\Console\Commands\SecretCommand;
use Redbeed\OpenOverlay\Console\Commands\Twitch\RefresherCommand;

class ConsoleServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->registerGlobalCommands();

        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
        }
    }

    protected function registerConsoleCommands(): void
    {
        $this->commands([
            EventSubListingCommand::class,
            EventSubDeleteCommand::class,
            EventBroadcastFaker::class,
            SecretCommand::class,
            ChatBotCommand::class,

            MakeBotCommandCommand::class,
            MakeBotSchedulingCommand::class,

            RefresherCommand::class,
        ]);
    }

    protected function registerGlobalCommands(): void
    {
        $this->commands([
            ChatBotMessageCommand::class,
        ]);
    }
}
