<?php

namespace Runtime\React;

use Psr\Http\Server\RequestHandlerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Runtime\RunnerInterface;

class Runner implements RunnerInterface
{
    private RequestHandlerInterface $application;
    private ServerFactory $serverFactory;

    public function __construct(ServerFactory $serverFactory, RequestHandlerInterface $application)
    {
        $this->serverFactory = $serverFactory;
        $this->application = $application;
    }

    public function run(): int
    {
        $this->serverFactory->createServer($this->application);

        return 0;
    }
}
