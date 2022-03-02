<?php

namespace Runtime\React;

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use Symfony\Component\Runtime\RunnerInterface;

class Runner implements RunnerInterface
{
    private $application;
    private $host;
    private $port;

    public function __construct($application, $host, $port)
    {
        $this->application = $application;
        $this->host = $host;
        $this->port = $port;
    }

    public function run(): int
    {
        $application = $this->application;
        $loop = Factory::create();

        $server = new HttpServer($loop, function (ServerRequestInterface $request) use ($application) {
            return $application->handle($request);
        });

        $socket = new SocketServer(sprintf('%s:%s', $this->host, $this->port), $loop);
        $server->listen($socket);

        $loop->run();

        return 0;
    }
}
