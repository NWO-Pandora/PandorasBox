<?php
declare(strict_types=1);

namespace NWO\Pandora\Box\Commands;

use Discord\Parts\Channel\Message;
use NWO\Pandora\Box;
use NWO\Pandora\Box\Command;

/**
 * Help command.
 *
 * @author Pandora <Pandora#4192>
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "Help";

    /** @inheritDoc */
    public const Aliases = ["!Help", "help", "!help"];

    /** @inheritDoc */
    public const Description = <<<Description
``Help``
Prints this help message.
Description;

    /** @inheritDoc */
    public function Execute(Message $Message, string $Command = ""): void {
        if(isset(Box::$Commands[$Command])) {
            $Message->reply(static::Format(Box::$Commands[$Command]));
            return;
        }

        $Text = "";
        $Count = 0;
        foreach(Box::$Commands as $Command) {
            $Text .= static::Format($Command);
            if(++$Count === 5) {
                $Message->reply($Text);
                $Text = "";
                $Count = 0;
            }
        }
        if($Text !== "") {
            $Message->reply($Text);
        }
    }

    /**
     * Formats a Command into a printable help message.
     *
     * @param Command $Command The Command to format.
     *
     * @return string A string containing a formatted description of the specified Command.
     */
    public static function Format(Command $Command): string {
        $Text = "**__" . $Command::Name . "__**\r\n" . $Command::Description . "\r\n\r\n";
        if(isset($Command::Aliases[0])) {
            $Text .= "__Aliases__\r\n``" . \implode("`` ``", $Command::Aliases) . "``\r\n\r\n";
        }
        $Text .= "__Authentication required__\r\n``" . \json_encode($Command::Authentication) . "``\r\n\r\n";
        return $Text;
    }

};