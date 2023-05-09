<?php
declare(strict_types=1);

use Discord\Parts\Channel\Message;
use NWO\Pandora\Box;
use NWO\Pandora\Box\Command;

/**
 * Admins command.
 *
 * @author Pandora <Pandora#4192>
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "Admins";

    /** @inheritDoc */
    public const Aliases = ["!Admins", "admins", "!admins"];

    /** @inheritDoc */
    public const Description = <<<Description
``Admins``
Shows the list of current administrators.
Description;

    /** @inheritDoc */
    public function Execute(Message $Message): void {
        $Text = "__Current Admins__\r\n";
        foreach(Box::$Admins as $Admin) {
            $Text .= "<@$Admin>\r\n";
        }
        $Message->reply($Text);
    }
};