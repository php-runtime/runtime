<?php

namespace Runtime\Swoole;

use Illuminate\Contracts\Http\Kernel;
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
    private const DEFAULT_OPTIONS = [
        'host' => '127.0.0.1',
        'port' => 8000,
        'mode' => SWOOLE_PROCESS,
        'settings' => []
    ];

    public function __construct(array $options)
    {
        $options['host'] = $options['host'] ?? $_SERVER['SWOOLE_HOST'] ?? $_ENV['SWOOLE_HOST'] ?? self::DEFAULT_OPTIONS['host'];
        $options['port'] = $options['port'] ?? $_SERVER['SWOOLE_PORT'] ?? $_ENV['SWOOLE_PORT'] ?? self::DEFAULT_OPTIONS['port'];
        $options['mode'] = $options['mode'] ?? $_SERVER['SWOOLE_MODE'] ?? $_ENV['SWOOLE_MODE'] ?? self::DEFAULT_OPTIONS['mode'];

        $options = array_replace_recursive(self::DEFAULT_OPTIONS, $options);

        parent::__construct($options);
    }

    public function getRunner(?object $application): RunnerInterface
    {
        if (is_callable($application)) {
            return new Runner($application, $this->options);
        }

        if ($application instanceof HttpKernelInterface) {
            return new SymfonyRunner($application, $this->options);
        }

        if ($application instanceof Kernel) {
            return new LaravelRunner($application, $this->options);
        }

        return parent::getRunner($application);
    }
}
