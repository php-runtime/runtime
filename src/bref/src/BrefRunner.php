<?php

namespace Runtime\Bref;

use Bref\Event\Handler;
use Bref\Runtime\LambdaRuntime;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * This will run BrefHandlers.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BrefRunner implements RunnerInterface
{
    private $handler;
    private $loopMax;

    public function __construct(Handler $handler, int $loopMax)
    {
        $this->handler = $handler;
        $this->loopMax = $loopMax;
    }

    public function run(): int
    {
        $lambda = LambdaRuntime::fromEnvironmentVariable();

        $loops = 0;
        while (true) {
            if (++$loops > $this->loopMax) {
                return 0;
            }
            $lambda->processNextEvent($this->handler);
        }
    }
}
