<?php
declare(strict_types=1);

use Discord\Parts\Channel\Message;
use NWO\Pandora\Box;
use NWO\Pandora\Box\Command;

/**
 * Command that closes Pandora's Box.
 *
 * @author Pandora <Pandora#4192>
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "Close";

    /** @inheritDoc */
    public const Aliases = ["!Close", "close", "!close"];

    /** @inheritDoc */
    public const Description = <<<Description
``Close``
Closes Pandora's Box.
Description;

    /** @inheritDoc */
    public const Authentication = true;

    /** @inheritDoc */
    public function Execute(Message $Message): void {
        $Message->reply("The box has been closed!")
                ->done(static fn() => Box::Close());
    }
};