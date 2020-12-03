<?php

namespace Redbeed\OpenOverlay\Console;

use Illuminate\Support\ServiceProvider;
use Redbeed\OpenOverlay\Console\Commands\EventSubDeleteCommand;
use Redbeed\OpenOverlay\Console\Commands\EventSubListingCommand;

class ConsoleServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    protected function registerCommands(): void
    {
        $this->commands([
            EventSubListingCommand::class,
            EventSubDeleteCommand::class,
        ]);
    }
}
