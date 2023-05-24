<?php
declare(strict_types=1);

use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use NWO\Pandora\Box;
use NWO\Pandora\Box\Command;
use NWO\Pandora\Box\Promise;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * Wipe Command.
 *
 * @author Pandora <Pandora#4192>
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "Nuke";

    /** @inheritDoc */
    public const Aliases = ["!Nuke", "nuke", "!nuke"];

    /** @inheritDoc */
    public const Description = <<<Description
``Nuke``
Nukes the entire server.
Deletes all messages, channels and bans all users (except for admins) from the current server.

:warning: **__Use with caution__** :warning:
Description;

    /** @inheritDoc */
    public const Authentication = true;

    /** @inheritDoc */
    public function Execute(Message $Message, ?string $Reason = null): void {

        if($Reason !== null){
            /** @var \Discord\Parts\User\Member $User */
            foreach($Message->guild->members as $User){
                if($User->id !== Box::$Client->id){
                    $User->sendMessage($Reason);
                }
            }
        }

        static::DeleteMessages($Message->guild)
              ->then(static fn() => static::DeleteChannels($Message->guild))
              ->then(static fn() => static::BanUsers($Message->guild));
    }

    /**
     * Deletes all messages from a specified server.
     *
     * @param Guild $Server The server to delete all messages from.
     *
     * @return PromiseInterface
     */
    public static function DeleteMessages(Guild $Server): PromiseInterface {
        $Channels = [];
        /** @var Channel $Channel */
        foreach($Server->channels as $Channel) {
            var_dump("clearing $Channel->name");
            $Channels[] = static::ClearChannel($Channel);
        }
        return Promise::All(...$Channels);
    }

    /**
     * Clears all messages from a specified channel.
     *
     * @param Channel $Channel
     *
     * @return PromiseInterface
     */
    public static function ClearChannel(Channel $Channel): PromiseInterface {
        if($Channel->type !== Channel::TYPE_TEXT) {
            return resolve(true);
        }
        $Clear = static function(?string $ID) use (&$Clear, $Channel) {
            if($ID === null) {
                return;
            }
            $Channel->getMessageHistory(["before" => $ID])
                    ->done(static function(Collection $Messages) use (&$Clear, $Channel) {
                        if($Messages->count() === 0) {
                            return;
                        }
                        $Channel->deleteMessages($Messages)
                                ->done(static fn() => $Clear($Messages?->last()?->ID));
                    });
        };
        return resolve($Clear($Channel->last_message_id));
    }


    /**
     * Deletes all channels from a specified server.
     *
     * @param Guild $Server The server to delete all channels from.
     *
     * @return PromiseInterface
     */
    public static function DeleteChannels(Guild $Server): PromiseInterface {
        $Channels = [];
        /** @var Channel $Channel */
        foreach($Server->channels as $Channel) {
            var_dump("deleting $Channel->name");
            $Channels[] = $Server->channels->delete($Channel);
        }
        return Promise::All(...$Channels);
    }

    /**
     * Bans all users from a specified server.
     *
     * @param Guild $Server The server to ban all users from.
     *
     * @return PromiseInterface
     */
    public static function BanUsers(Guild $Server): PromiseInterface {
        $Users = [];
        /** @var \Discord\Parts\User\Member $User */
        foreach($Server->members as $User) {
            if(!\in_array($User->id, Box::$Admins)) {
                var_dump("banning $User->displayname");
                $Users[] = $User->ban(7);
            }
        }
        return Promise::All(...$Users);
    }

};
