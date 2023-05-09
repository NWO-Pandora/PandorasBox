<?php
declare(strict_types=1);

namespace NWO\Pandora\Box\Async;

use Discord\Parts\Channel\Message;

/**
 * Abstract class for async bot commands.
 *
 * @author Pandora <Pandora#4192>
 */
abstract class Command extends \NWO\Pandora\Box\Command {

    /**
     * Executes the ICommand according the received Message.
     *
     * @param Message $Message The Message that invoked the ICommand.
     *
     * @return \Generator A Generator that executes the Command asynchronously.
     */
     abstract public function Execute(Message $Message): \Generator;

}