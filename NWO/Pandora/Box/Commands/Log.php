<?php
declare(strict_types=1);

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Invite;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Role;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use NWO\Pandora\Box;
use NWO\Pandora\Box\Command;
use React\Promise\PromiseInterface;

/**
 * Restarts the Box.
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "Log";

    /** @inheritDoc */
    public const Aliases = ["!Log", "log", "!log"];

    /**
     * The ID of the channel where log output is written to.
     */
    public const Channel = "1105064878659616808";

    /** @inheritDoc */
    public const Description = <<<Description
``Restart``
Restarts the bot.
Description;

    /** @inheritDoc */
    public const Authentication = true;

    /**
     * @param bool         $Running   Flag indicating whether the Log is running.
     * @param array        $Listeners The listeners of the Log.
     * @param Channel|null $Channel   The output channel of the Log.
     */
    public function __construct(public bool $Running = false, protected array $Listeners = [], protected ?Channel $Channel = null) {

        /** @var \Discord\Parts\Guild\Guild $Guild */
        foreach(Box::$Client->guilds as $Guild) {
            /** @var Channel $Channel */
            foreach($Guild->channels as $Channel) {
                if($Channel->id === static::Channel) {
                    $this->Channel = $Channel;
                }
            }
        }

        $this->Listeners = [
            //Message updated.
            Event::MESSAGE_UPDATE      => function(Message $New, Discord $Client, ?Message $Old) {
                $Previous = ($Old?->content) ?? "N/A";
                $Text = "<@{$New->author->id}> {$New->link}";
                if(\strlen($Text) + \strlen($New->content) + \strlen($Previous) <= 1900) {
                    $this->Info("Message updated", $Text . "\r\n__Old__\r\n```{$Previous}```\r\n__New__\r\n```\r\n{$New->content}\r\n```");
                } else {
                    $this->Info("Message updated", $Text)
                         ->then(fn() => $this->Info("__Old__", "```{$Old?->content}```"))
                         ->then(fn() => $this->Info("__New__", "```{$New->content}```"));
                }
            },
            //Message deleted.
            Event::MESSAGE_DELETE      => function(object $Message) {
                if($Message instanceof Message) {
                    $this->Error("Message deleted", "<@{$Message->author->id}>\r\n```{$Message->content}```");
                } else {
                    $this->Error("Message deleted", "$Message->id\r\n<#{$Message->channel_id}>");
                }
            },
            //User join.
            Event::GUILD_MEMBER_ADD    => fn(Member $Member) => $this->Add("User joined", "<@{$Member->id}> {$Member->displayname}"),
            //User left.
            Event::GUILD_MEMBER_REMOVE => fn(Member $Member) => $this->Write("User left", "<@{$Member->id}> {$Member->displayname}"),
            //User updated.
            Event::GUILD_MEMBER_UPDATE => function(Member $New, Discord $Client, ?Member $Old) {
                if($New->roles->count() !== $Old?->roles?->count() ?? 0) {
                    if($New->roles->count() > $Old?->roles?->count()) {
                        $this->Add(
                            "Added roles",
                            "<@{$New->id}>" . \implode(", ", \array_map(
                                    static fn(Role $Role) => "<@&{$Role->id}>",
                                    \array_diff($New->roles->toArray(), $Old->roles->toArray()))
                            )
                        );
                    } else {
                        $this->Error(
                            "Removed roles",
                            "<@{$New->id}>" . \implode(", ", \array_map(
                                    static fn(Role $Role) => "<@&{$Role->id}>",
                                    \array_diff($Old->roles->toArray(), $New->roles->toArray()))
                            )
                        );
                    }
                }
                if($New->displayname !== $Old?->displayname) {
                    $this->Info("User renamed", "<@{$New->id}> from ``{$Old?->displayname}`` to ``{$New->displayname}`` ");
                }
            },
            //Invite created.
            Event::INVITE_CREATE       => function(Invite $Invite) {
                if($Invite->target_user) {
                    $this->Add("Created invite", "<@{$Invite->inviter->id}> https://discord.gg/{$Invite->code} for {$Invite->target_user->username}");
                    return;
                }
                $this->Add("Created invite", "<@{$Invite->inviter->id}> https://discord.gg/{$Invite->code}");
            }
        ];
    }

    /** @inheritDoc */
    public function Execute(Message $Message, string $Action = ""): void {
        switch(\strtolower($Action)) {
            case "start":
                if($this->Running) {
                    $Message->reply("Log already running!");
                    return;
                }
                foreach($this->Listeners as $Event => $Listener) {
                    Box::$Client->on($Event, $Listener);
                }
                $this->Running = true;
                $Message->reply("Log started!");
                break;
            case "stop":
                if(!$this->Running) {
                    $Message->reply("Log not running!");
                    return;
                }
                foreach($this->Listeners as $Event => $Listener) {
                    Box::$Client->removeListener($Event, $Listener);
                }
                $this->Running = false;
                $Message->reply("Log stopped!");
                break;
            case "write":
                $this->Channel->sendMessage(MessageBuilder::new()->setContent($Text));
                break;
            default:
                $Message->reply($this->Running ? "Log running!" : "Log not running!");
                break;
        }
    }

    /**
     * @param string $Topic
     * @param string $Message
     * @param int    $Color
     */
    public function Write(string $Topic, string $Message, int $Color = 0x000000): PromiseInterface {
        return $this->Channel->sendMessage(
            MessageBuilder::new()->addEmbed(
                (new Embed(Box::$Client))
                    ->setTitle($Topic)
                    ->setDescription($Message)
                    ->setColor($Color)
            )
        );
    }

    public function Warn(string $Topic, string $Message): PromiseInterface {
        return $this->Write($Topic, $Message, 0xF0DE63);
    }

    public function Error(string $Topic, string $Message): PromiseInterface {
        return $this->Write($Topic, $Message, 0xF0734B);
    }

    public function Info(string $Topic, string $Message): PromiseInterface {
        return $this->Write($Topic, $Message, 0x70DEF3);
    }

    public function Add(string $Topic, string $Message): PromiseInterface {
        return $this->Write($Topic, $Message, 0x8CF370);
    }
};