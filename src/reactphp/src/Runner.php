<?php

namespace Runtime\React;

use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Runtime\RunnerInterface;

class Runner implements RunnerInterface
{
    private RequestHandlerInterface $application;

    public function __construct(private ServerFactory $serverFactory, RequestHandlerInterface $application)
    {
        $this->application = $application;
    }

    public function run(): int
    {
        $loop = $this->serverFactory->createServer($this->application);
        $loop->run();

        return 0;
    }
}
