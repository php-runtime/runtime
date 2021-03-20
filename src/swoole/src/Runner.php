<?php

namespace Runtime\Swoole;

use Swoole\Http\Server;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A simple runner that will run a callable.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Runner implements RunnerInterface
{
    private $application;
    private $port;
    private $host;

    public function __construct(callable $application, $host, $port)
    {
        $this->application = $application;
        $this->host = $host;
        $this->port = $port;
    }

    public function run(): int
    {
        $server = new Server($this->host, $this->port);

        $server->on('request', $this->application);

        $server->start();

        return 0;
    }
}
