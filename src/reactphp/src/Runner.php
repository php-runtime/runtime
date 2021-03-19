<?php

namespace Runtime\React;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Runtime\RunnerInterface;

class Runner implements RunnerInterface
{
    private $application;
    private $port;

    public function __construct($application, $port)
    {
        $this->application = $application;
        $this->port = $port;
    }

    public function run(): int
    {
        $application = $this->application;
        $loop = \React\EventLoop\Factory::create();

        $server = new \React\Http\Server($loop, function (ServerRequestInterface $request) use ($application) {
            return $application->handle($request);
        });

        $socket = new \React\Socket\Server($this->port, $loop);
        $server->listen($socket);

        $loop->run();

        return 0;
    }
}
