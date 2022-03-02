<?php

namespace Runtime\React;

use Psr\Http\Server\RequestHandlerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Runtime\RunnerInterface;

class Runner implements RunnerInterface
{
    private RequestHandlerInterface $application;
    private ServerFactory $serverFactory;
    private LoopInterface $loop;

    public function __construct(ServerFactory $factory, LoopInterface $loop, RequestHandlerInterface $application)
    {
        $this->serverFactory = $factory;
        $this->loop = $loop;
        $this->application = $application;
    }

    public function run(): int
    {
        $this->serverFactory->createServer($this->loop, $this->application);
        $this->loop->run();

        return 0;
    }
}
