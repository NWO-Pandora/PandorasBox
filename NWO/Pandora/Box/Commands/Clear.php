<?php
declare(strict_types=1);

use Discord\Helpers\Collection;
use Discord\Parts\Channel\Message;
use NWO\Pandora\Box\Command;

/**
 * Clear Command.
 *
 * @author Pandora <Pandora#4192>
 */
return new class extends Command {

    /** @inheritDoc */
    public const Name = "Clear";

    /** @inheritDoc */
    public const Aliases = ["!Clear", "clear", "!clear"];

    /** @inheritDoc */
    public const Description = <<<Description
``Clear [Amount]``
Clears the entire channel or a specified amount of messages.
Description;

    /** @inheritDoc */
    public function Execute(Message $Message, $Amount = null): void {
        if((int)$Amount > 0) {
            $Message->channel->getMessageHistory(["before" => $Message->id, "limit" => (int)$Amount])
                             ->done(static fn(Collection $Messages) => $Message->channel->deleteMessages($Messages)
                                                                                        ->done(static fn() => $Message->delete()));
            return;
        }

        $Clear = static function(Message $Message) use (&$Clear) {
            $Message->channel->getMessageHistory(["before" => $Message->id])
                             ->done(static function(Collection $Messages) use ($Message, &$Clear) {
                                 if($Messages->count() === 0) {
                                     return;
                                 }
                                 $Message->channel->deleteMessages($Messages)
                                                  ->done(static fn() => $Clear($Messages->last()));
                             });
        };
        $Clear($Message);
        $Message->delete();
    }
};
