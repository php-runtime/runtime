<?php

namespace Runtime\Swoole;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

/**
 * A runtime for Swoole.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Runtime extends SymfonyRuntime
{
    public function __construct(array $options)
    {
        $options['swoole_host'] = $options['swoole_host'] ?? $_SERVER['SWOOLE_HOST'] ?? $_ENV['SWOOLE_HOST'] ?? '127.0.0.1';
        $options['swoole_port'] = $options['swoole_port'] ?? $_SERVER['SWOOLE_PORT'] ?? $_ENV['SWOOLE_PORT'] ?? 8000;

        parent::__construct($options);
    }

    public function getRunner(?object $application): RunnerInterface
    {
        if (is_callable($application)) {
            return new Runner($application, $this->options['swoole_host'], $this->options['swoole_port']);
        }

        if ($application instanceof HttpKernelInterface) {
            return new SymfonyRunner($application, $this->options['swoole_host'], $this->options['swoole_port']);
        }

        return parent::getRunner($application);
    }
}
