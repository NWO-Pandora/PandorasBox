<?php
declare(strict_types=1);

namespace NWO\Pandora\Box;

use NWO\Pandora\Box;
use React\EventLoop\Timer\Timer;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * \Generator based promise-implementation for react PHP.
 */
class Promise implements PromiseInterface {

    /**
     * The underlying Promise.
     * @var Deferred
     */
    protected Deferred $Promise;

    /**
     * Predefined value for resolving Promises.
     */
    public const Resolve = "Resolve";

    /**
     * Predefined value for rejecting Promises.
     */
    public const Reject = "Reject";

    /**
     * Initializes a new instance of the Promise class.
     *
     * @param \Closure $Closure Initializes the Promise with the specified Generator Closure to execute.
     */
    public function __construct(protected \Closure $Closure) {
        $this->Promise = new Deferred();
        $Generator = $Closure();
        if(!$Generator instanceof \Generator) {
            $this->Promise->reject(new \InvalidArgumentException("The specified Closure is not a Generator!"));
            return;
        }

        Box::$Loop->addPeriodicTimer(0.01, function(Timer $Timer) use ($Generator) {
            if($Generator->valid()) {
                try {
                    if($Generator->key() === static::Resolve) {
                        Box::$Loop->cancelTimer($Timer);
                        $this->Promise->resolve($Generator->current());
                        return;
                    }
                    if($Generator->key() === static::Reject) {
                        Box::$Loop->cancelTimer($Timer);
                        $this->Promise->reject($Generator->current());
                        return;
                    }
                    $Generator->next();
                } catch(\Throwable $Exception) {
                    Box::$Loop->cancelTimer($Timer);
                    $this->Promise->reject($Exception);
                }
            } else {
                Box::$Loop->cancelTimer($Timer);
                $this->Promise->resolve($Generator->getReturn());
            }
        });
    }

    /** @inheritDoc */
    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null): PromiseInterface {
        return $this->Promise->promise()->then($onFulfilled, $onRejected, $onProgress);
    }

    /**
     * Returns a new Promise that resolves when each specified Promise has been resolved.
     *
     * @param PromiseInterface ...$Promises The Promises to resolve.
     *
     * @return static A new Promise that resolves when all passed Promises have been resolved.
     */
    public static function All(PromiseInterface ...$Promises): static {
        return new static(
            static function() use ($Promises): \Generator {
                $Count = \count($Promises);
                $Resolved = 0;
                $Rejected = null;
                $Results = [];
                $Resolve = static function($Result) use (&$Resolved, &$Results) {
                    $Results[] = $Result;
                    $Resolved++;
                    return $Result;
                };
                $Reject = static function($Result) use (&$Rejected) {
                    $Rejected = $Result;
                    return $Result;
                };
                foreach($Promises as $Promise) {
                    $Promise->then($Resolve, $Reject);
                }
                yield;
                while($Resolved < $Count) {
                    yield;
                    if($Rejected !== null) {
                        yield Promise::Reject => $Rejected;
                        return;
                    }
                }
                yield Promise::Resolve => $Results;
            }
        );
    }
}