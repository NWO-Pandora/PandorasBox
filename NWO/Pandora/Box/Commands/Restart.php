<?php
declare(strict_types=1);

use Discord\Parts\Channel\Message;
use NWO\Pandora\Box;
use NWO\Pandora\Box\Command;

/**
 * Restarts the Box.
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "Restart";

    /** @inheritDoc */
    public const Aliases = ["!Restart", "restart", "!restart"];

    /** @inheritDoc */
    public const Description = <<<Description
``Restart``
Restarts the box.
Description;

    /** @inheritDoc */
    public const Authentication = true;

    /** @inheritDoc */
    public function Execute(Message $Message): void {
        $Message->reply("Restarting...")
                ->done(static function() use ($Message) {
                    $Command = Box::$File . " --Restart=\"{$Message->id}\" --Channel=\"{$Message->channel->id}\"";
                    `php $Command`;
                    Box::Close();
                });
    }
};