<?php


namespace Redbeed\OpenOverlay\ChatBot\Twitch;

use Ratchet\Client\WebSocket;
use Redbeed\OpenOverlay\ChatBot\Commands\BotCommand;
use Redbeed\OpenOverlay\ChatBot\Commands\SimpleBotCommands;
use Redbeed\OpenOverlay\Events\TwitchBotTokenExpires;
use Redbeed\OpenOverlay\Events\TwitchChatMessageReceived;
use Redbeed\OpenOverlay\Models\BotConnection;

class ConnectionHandler
{
    const TWITCH_IRC_URL = 'wss://irc-ws.chat.twitch.tv:443';

    /** @var WebSocket */
    private $connection;

    /** @var BotConnection */
    private $bot;

    /** @var BotCommand[] */
    private $customCommands = [];

    /** @var string[] */
    private $joinedChannel = [];

    /** @var array[] */
    private $channelQueue = [];

    /** @var mixed[] */
    private $joinedCallBack = [];


    public function __construct(WebSocket $connection)
    {
        $this->connection = $connection;

        $this->connection->on('message', function ($message) {
            $this->messageReceived($message);
        });
    }

    public function messageReceived(string $message): void
    {

        // get join message
        if (strpos($message, 'NOTICE * :Login authentication failed') !== false) {
            echo "LOGIN | " . $message . "\r\n\r\n";
            event(new TwitchBotTokenExpires($this->bot));

            $this->connection->close();
            return;
        }

        // get join message
        if (strpos($message, 'PING') !== false) {
            $this->pingReceived($message);

            return;
        }

        // if is chat message
        if (strpos($message, 'PRIVMSG') !== false) {
            $this->chatMessageReceived($message);

            return;
        }

        // get join message
        if (strpos($message, 'JOIN') !== false) {
            $this->joinMessageReceived($message);

            return;
        }

        echo "UNKOWN | " . $message . "\r\n\r\n";
    }

    public function pingReceived(string $message): void
    {
        $this->send('PONG :tmi.twitch.tv');
        echo "PING PONG done" . "\r\n";
    }

    public function joinMessageReceived(string $message): void
    {
        try {
            preg_match("/:(.*)\!.*#(.*)/", $message, $matches);

            echo "BOT (" . $matches[1] . ") joined " . $matches[2] . "\r\n";
            $channelName = trim(strtolower($matches[2]));

            $this->joinedChannel[] = $channelName;
            $this->runChannelQueue($channelName);

            if (isset($this->joinedCallBack[$channelName])) {
                $this->joinedCallBack[$channelName]();
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage() . ' ' . $exception->getLine() . "\r\n";
        }
    }

    public function addJoinedCallBack(string $channelName, callable $callback): void
    {
        $channelName = strtolower($channelName);
        $this->joinedCallBack[$channelName] = $callback;
    }

    public function chatMessageReceived(string $message): void
    {
        $model = ChatMessage::parseIRCMessage($message);

        if ($model === null) {
            return;
        }

        echo $model->channel . ' | ' . $model->username . ': ' . $model->message . "\r\n";

        try {
            // Check commands
            foreach ($this->customCommands as $commandHandler) {
                $commandHandler->handle($model);
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage() . "\r\n";
            echo $exception->getFile() . "\r\n";
            echo $exception->getLine() . "\r\n";
        }

        echo $model->channel . ' | ' . $model->username . ': ' . $model->message . " HANDELD\r\n";

        try {
            event(new TwitchChatMessageReceived($model));
        } catch (\Exception $exception) {
            echo "  -> EVENT ERROR: " . $exception->getMessage();
        }
    }

    public function auth(BotConnection $bot)
    {
        $this->bot = $bot;

        $this->send('PASS oauth:' . $this->bot->service_token);
        $this->send('NICK ' . strtolower($this->bot->bot_username));
    }

    public function send(string $message): void
    {
        $this->connection->send($message);
    }


    public function joinChannel(string $channelName): void
    {
        $channelName = strtolower($channelName);

        $this->channelQueue[$channelName] = [];
        $this->send('JOIN #' . strtolower($channelName));
    }

    private function runChannelQueue(string $channelName): void
    {
        $channelName = trim(strtolower($channelName));

        if (!empty($this->channelQueue[$channelName])) {
            foreach ($this->channelQueue[$channelName] as $item) {
                $this->send($item);
            }
        }

        $this->channelQueue[$channelName] = [];
    }

    public function sendChatMessage(string $channelName, string $message): void
    {
        $lowerChannelName = strtolower($channelName);
        $message = 'PRIVMSG #' . $lowerChannelName . ' :' . $message . "\n\r";

        // send message after channel joined
        if (!in_array($lowerChannelName, $this->joinedChannel)) {
            $this->channelQueue[$lowerChannelName][] = $message;

            return;
        }

        $this->send($message);
        echo $message;
    }

    public function initCustomCommands(): void
    {
        /** @var BotCommand[] $commandClasses */
        $commandClasses = config('openoverlay.bot.commands.advanced');

        // add simple command handler
        $commandClasses[] = SimpleBotCommands::class;

        foreach ($commandClasses as $commandClass) {
            $this->customCommands[] = new $commandClass($this);
        }
    }

}
