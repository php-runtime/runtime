<?php

namespace Runtime\React;

use Psr\Http\Server\RequestHandlerInterface;
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
        $loop = $this->serverFactory->createServer($this->application);
        $loop->run();

        return 0;
    }
}
