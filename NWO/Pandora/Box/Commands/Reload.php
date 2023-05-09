<?php
declare(strict_types=1);

use Discord\Parts\Channel\Message;
use NWO\Pandora\Box;
use NWO\Pandora\Box\Command;

/**
 * Command that reloads the Commands and config(in the future) of the Box.
 *
 * @author Pandora <Pandora#4192>
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "Reload";

    /** @inheritDoc */
    public const Aliases = ["!Reload", "reload", "!reload"];

    /** @inheritDoc */
    public const Description = <<<Description
``Reload``

__Description__
Reloads the commands and configuration of the box.
Description;

    /** @inheritDoc */
    public const Authentication = true;

    /** @inheritDoc */
    public function Execute(Message $Message): void {
        $Old = \implode("``, ``", \array_keys(Box::$Commands));

        //Reload external Commands.
        foreach(\array_diff(\scandir(Box::$Path), ["..", "."]) as $File) {
            $Command = include Box::$Path . DIRECTORY_SEPARATOR . $File;
            if(!$Command instanceof Command) {
                continue;
            }
            Box::$Commands[$Command::Name] = $Command;
        }
        echo "External Commands reloaded." . PHP_EOL;

        //Reload internal Commands.
        foreach(\array_diff(\scandir(Box::Path), ["..", "."]) as $File) {
            $Command = include Box::Path . DIRECTORY_SEPARATOR . $File;
            if(!$Command instanceof Command) {
                continue;
            }
            Box::$Commands[$Command::Name] = $Command;
        }
        echo "Internal Commands reloaded." . PHP_EOL;

        $Message->reply("**Commands reloaded!**\r\n\r\n__Old__\r\n``$Old``\r\n\r\n__New__\r\n``" . \implode("``, ``", \array_keys(Box::$Commands)) . "``");
    }
};
