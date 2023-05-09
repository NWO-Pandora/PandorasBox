<?php
declare(strict_types=1);

namespace NWO\Pandora;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Intents;
use Monolog\Logger;
use NWO\Pandora\Box\Command;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;

/**
 * Pandora's box.
 *
 * @author Pandora <Pandora#4192>
 */
class Box {

    /**
     * The underlying discord client.
     * @var Discord|null
     */
    public static ?Discord $Client = null;

    /**
     * The event loop of the discord client.
     * @var LoopInterface|null
     */
    public static ?LoopInterface $Loop = null;

    /**
     * The Commands of the Box.
     * @var \NWO\Pandora\Box\Command[]
     */
    public static array $Commands = [];

    /**
     * The channels the box listens/responds to.
     * @var string[]
     */
    public static array $Channels = [];

    /**
     * The servers the box is a member of.
     * @var string[]
     */
    public static array $Servers = [];

    /**
     * Enumeration of names and tags of administrative users.
     * @var string[]
     */
    public static array $Admins = [];

    /**
     * The start time of the Box.
     * @var \DateTime|null
     */
    public static ?\DateTime $Start = null;

    /**
     * The path to the Command directory.
     */
    public const Path = __DIR__ . \DIRECTORY_SEPARATOR . "Box" . \DIRECTORY_SEPARATOR . "Commands";

    /**
     * The path of the executing php file of the Box.
     * @var string
     */
    public static string $File = "";

    /**
     * The path of the executing php file of the Box.
     * @var string
     */
    public static string $Path = "";

    /**
     * Opens Pandora's box with the specified bot token, channels and administrators.
     *
     * @param string   $Token    The discord bot token to use.
     * @param string[] $Channels The channels to listen on and respond to messages.
     * @param string[] $Admins   The users that can execute administrative Commands.
     * @param string   $File     The path to the invoking client file.
     * @param string   $Path     The path to external Commands.
     *
     * @throws \Discord\Exceptions\IntentException
     */
    public static function Open(string $Token, array $Channels, array $Admins, string $File, string $Path): void {
        static::$Channels = $Channels;
        static::$Admins = $Admins;
        static::$File = $File;
        static::$Path = $Path;

        //Start Bot.
        static::$Client = new Discord([
            "token"          => $Token,
            "intents"        => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT | Intents::GUILD_MEMBERS,
            "loadAllMembers" => true
        ]);

        static::$Loop = static::$Client->getLoop();
        static::Log("Discord client initialized.");

        static::$Client->on("ready", static function(Discord $Discord) {
            static::Log("Discord client started.");
            static::$Start = new \DateTime();

            foreach(static::$Client->guilds as $Guild) {
                static::$Servers[$Guild->id] = $Guild->name;
            }
            static::$Admins[] = static::$Client->id;

            //load commands.
            foreach(\array_diff(\scandir(static::$Path), ["..", "."]) as $File) {
                $Command = include static::$Path . \DIRECTORY_SEPARATOR . $File;
                if(!$Command instanceof Command) {
                    continue;
                }
                static::$Commands[$Command::Name] = $Command;
            }
            static::Log("External Commands loaded.");

            //Load built-in commands.
            foreach(\array_diff(\scandir(static::Path), ["..", "."]) as $File) {
                $Command = include static::Path . \DIRECTORY_SEPARATOR . $File;
                if(!$Command instanceof Command) {
                    continue;
                }
                static::$Commands[$Command::Name] = $Command;
            }
            static::Log("Internal Commands loaded.");

            //Main logic of Message handling.
            $Discord->on("message", static function(Message $Message) {

                //Check if the message originates from a listened channel.
                if($Message->author->bot || !\in_array($Message->channel_id, static::$Channels)) {
                    return;
                }

                //Check if (aliased) command exists.
                $Command = null;
                $Parameters = [];
                foreach(static::$Commands as $RegisteredCommand) {
                    if(\str_starts_with($Message->content, $RegisteredCommand::Name)) {
                        $Command = $RegisteredCommand;
                        $Parameters = \array_map("trim", \explode(",", \ltrim($Message->content, $RegisteredCommand::Name . " ")));
                        break;
                    }
                    foreach($RegisteredCommand::Aliases as $Alias) {
                        if(\str_starts_with($Message->content, $Alias)) {
                            $Parameters = \array_map("trim", \explode(",", \ltrim($Message->content, "$Alias ")) ?? []);
                            $Command = $RegisteredCommand;
                            break 2;
                        }
                    }
                }

                if(!\is_array($Parameters)) {
                    $Parameters = [];
                }

                //Check if a Command has been found.
                if($Command === null) {
                    return;
                }

                //Check if the Command requires authentication.
                if($Command::Authentication && !\in_array($Message->author->id, static::$Admins)) {
                    return;
                }

                //Check if the Command has a channel limitation.
                if(isset($Command::Channels[0]) && !\in_array($Message->channel->id, $Command::Channels)) {
                    return;
                }

                //Execute Command.
                try {
                    if($Command instanceof Box\Async\Command) {
                        if(($Parameters[0] ?? "") !== "") {
                            $Generator = $Command->Execute($Message, ...$Parameters);
                        } else {
                            $Generator = $Command->Execute($Message);
                        }
                        static::$Loop->addPeriodicTimer(0.01, static function(Timer $Timer) use ($Generator) {
                            if($Generator->valid()) {
                                $Generator->next();
                            } else {
                                static::$Loop->cancelTimer($Timer);
                            }
                        });
                    } else {
                        if(($Parameters[0] ?? "") !== "") {
                            $Command->Execute($Message, ...$Parameters);
                        } else {
                            $Command->Execute($Message);
                        }
                    }
                } catch(\Throwable $Exception) {
                    static::Log($Exception->getMessage());
                    static::Log($Exception->getTraceAsString());
                }
            });

            //Answer on Restart Commands.
            $Parameters = \getopt("R:C:", ["Restart:", "Channel:"]);
            if(
                (isset($Parameters["R"]) || isset($Parameters["Restart"])) &&
                (isset($Parameters["C"]) || isset($Parameters["Channel"]))
            ) {
                echo $Parameters["R"] ?? $Parameters["Restart"];
                /** @var \Discord\Parts\Guild\Guild $Server */
                foreach($Discord->guilds as $Server) {
                    /** @var \Discord\Parts\Channel\Channel $Channel */
                    foreach($Server->channels as $Channel) {
                        if($Channel->id === ($Parameters["C"] ?? $Parameters["Channel"])) {
                            $Channel->messages->fetch($Parameters["R"] ?? $Parameters["Restart"])
                                              ->done(fn(Message $Message) => $Message->reply("Here we go again!"));
                        }
                    }
                }
                static::Log("Restarted.");
            } else {
                static::Log("Opened.");
            }
        });
        static::$Client->run();
        echo "Discord client started." . PHP_EOL;
    }

    /**
     * Closes Pandora's box.
     */
    public static function Close(): void {
        static::$Client->close();
        static::Log("Closed.");
        exit;
    }

    /**
     * @param string $Message
     */
    public static function Log(string $Message): void {
        echo "[" . (new \DateTime())->format(\DATE_ATOM) . "] Pandora's Box: $Message" . \PHP_EOL;
    }
}