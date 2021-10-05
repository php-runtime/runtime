<?php

namespace Runtime\Bref;

use Bref\Event\Handler;
use Runtime\Bref\Lambda\LambdaClient;
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
        $lambda = LambdaClient::fromEnvironmentVariable('symfony-runtime');

        $loops = 0;
        while (true) {
            if (++$loops > $this->loopMax) {
                return 0;
            }

            /**
             * In case the execution failed, we force starting a new process regardless
             * of $this->loopMax. This is because an uncaught exception could have
             * left the application in a non-clean state.
             */
            if (!$lambda->processNextEvent($this->handler)) {
                return 0;
            }
        }
    }
}
