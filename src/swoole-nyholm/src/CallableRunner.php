<?php

namespace Runtime\SwooleNyholm;

use Symfony\Component\Runtime\RunnerInterface;

/**
 * A simple runner that will run a callable.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CallableRunner implements RunnerInterface
{
    /** @var callable */
    private $application;

    public function __construct(private ServerFactory $serverFactory, callable $application)
    {
        $this->application = $application;
    }

    public function run(): int
    {
        $this->serverFactory->createServer($this->application)->start();

        return 0;
    }
}
