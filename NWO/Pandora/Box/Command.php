<?php
declare(strict_types=1);

namespace NWO\Pandora\Box;

use Discord\Parts\Channel\Message;

/**
 * Abstract class for bot commands.
 *
 * @author Pandora <Pandora#4192>
 */
abstract class Command {

    /**
     * The name of the Command
     */
    public const Name = "Command";

    /**
     * The aliases of the Command.
     */
    public const Aliases = [];

    /**
     * The channel limitation of the command.
     */
    public const Channels = [];

    /**
     * Flag indicating whether only administrators are allowed to execute the Command.
     */
    public const Authentication = false;

    /**
     * The description of the Command.
     */
    public const Description = "";

    /**
     * Executes the ICommand according the received Message.
     *
     * @param Message $Message The Message that invoked the ICommand.
     */
     abstract public function Execute(Message $Message);

}