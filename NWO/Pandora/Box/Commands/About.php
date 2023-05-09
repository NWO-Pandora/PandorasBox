<?php
declare(strict_types=1);

use Discord\Parts\Channel\Message;
use NWO\Pandora\Box\Command;

/**
 * About command.
 *
 * @author Pandora <Pandora#4192>
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "About";

    /** @inheritDoc */
    public const Aliases = ["!About", "about", "!about"];

    /** @inheritDoc */
    public const Description = <<<Description
``About``
Meddl!
Description;

    /** @inheritDoc */
    public function Execute(Message $Message): void {
        $Message->reply(<<<About
```
  _____  _______ __   _ ______   _____   ______ _______ _______     ______   _____  _     _
 |_____] |_____| | \  | |     \ |     | |_____/ |_____| |______     |_____] |     |  \___/ 
 |       |     | |  \_| |_____/ |_____| |    \_ |     | ______|     |_____] |_____| _/   \_
```
Async discord bot written in PHP.

https://github.com/NWO-Pandora/PandorasBox

Made by <@1076238956871565403>
About
);
    }
};