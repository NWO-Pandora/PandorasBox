<?php
declare(strict_types=1);

use Discord\Parts\Channel\Message;
use NWO\Pandora\Box;
use NWO\Pandora\Box\Async\Command;

/**
 * Status Command.
 *
 * @author Pandora <Pandora#4192>
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "Status";

    /** @inheritDoc */
    public const Aliases = ["!Status", "status", "!status"];

    /** @inheritDoc */
    public const Description = <<<Description
``Status``
Prints info about current memory and CPU usage.
Description;

    /** @inheritDoc */
    public function Execute(Message $Message): \Generator {
        $Text = "**__Status__**\r\nRunning since: " . Box::$Start->diff(new \DateTime())->format("``%d`` days ``%h`` hours ``%i`` minutes ``%s`` seconds")
                . "\r\nStarted: ``" . Box::$Start->format("d.m.Y`` ``H:i:s") . "``\r\n\r\n";
        yield;
        $Limit = \ini_get("memory_limit");
        $Text .= "__Ram__\r\nUsage: ``{$this->Format(\memory_get_usage(true))}/$Limit``\r\nPeak: ``{$this->Format(\memory_get_peak_usage())}/$Limit``\r\n\r\n";
        yield;
        $Text .= "__CPU__\r\nUsage: ``" . (int)\str_replace("LoadPercentage", "", `wmic cpu get LoadPercentage`) . "``%\r\n\r\n";
        yield;
        $Text .= "__Commands__\r\n``" . \implode("`` ``", \array_keys(Box::$Commands)) . "``\r\n\r\n";
        yield;
        $Text .= "__Servers__\r\n" . \implode(", ",  Box::$Servers) . "\r\n\r\n";
        yield;
        $Text .= "__Channels__\r\n" . \implode(", ", \array_map(static fn($Channel) => "<#$Channel>", Box::$Channels)) . "\r\n\r\n";

        $Message->reply($Text);
    }

    public function Format(int $Bytes): string {
        return \round($Bytes / \pow(1024, ($Unit = \floor(\log($Bytes, 1024))))) . ["B", "K", "M", "G"][$Unit];
    }
};