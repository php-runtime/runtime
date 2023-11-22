<?php

namespace Runtime\Bref;

use Runtime\Bref\Lambda\LambdaClient;
use Symfony\Component\Console\Application;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * This will run normal "symfony console" applications.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ConsoleApplicationRunner implements RunnerInterface
{
    private $handler;
    private $loopMax;

    public function __construct(Application $application, int $loopMax = 1)
    {
        $this->handler = new ConsoleApplicationHandler($application);
        $this->loopMax = $loopMax;
    }

    public function run(): int
    {
        $lambda = LambdaClient::fromEnvironmentVariable('symfony-runtime-console');

        $loops = 0;
        while (true) {
            if (++$loops > $this->loopMax) {
                return 0;
            }

            /*
             * In case the execution failed, we force starting a new process. This
             * is because an uncaught exception could have left the application
             * in a non-clean state.
             */
            if (!$lambda->processNextEvent($this->handler)) {
                return 0;
            }
        }
    }
}
