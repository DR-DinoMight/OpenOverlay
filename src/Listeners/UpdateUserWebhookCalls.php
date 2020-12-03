<?php

namespace Redbeed\OpenOverlay\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Redbeed\OpenOverlay\Actions\RegisterUserTwitchWebhooks;
use Redbeed\OpenOverlay\Events\UserConnectionChanged;

class UpdateUserWebhookCalls implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function handle(UserConnectionChanged $event)
    {
        $twitchConnection = $event->user->connections()->where('service', 'twitch')->first();
        RegisterUserTwitchWebhooks::registerAll($twitchConnection);
    }
}
