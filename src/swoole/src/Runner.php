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
    private $options;

    public function __construct(callable $application, array $options)
    {
        $this->application = $application;
        $this->options = $options;
    }

    public function run(): int
    {
        $server = new Server($this->options['host'], $this->options['port'], $this->options['mode']);

        $server->set($this->options['settings']);

        $server->on('request', $this->application);

        $server->start();

        return 0;
    }
}
