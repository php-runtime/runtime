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

    public function __construct(Application $application)
    {
        $this->handler = new ConsoleApplicationHandler($application);
    }

    public function run(): int
    {
        $lambda = LambdaClient::fromEnvironmentVariable('symfony-runtime-console');

        while (true) {
            $lambda->processNextEvent($this->handler);
        }
    }
}
