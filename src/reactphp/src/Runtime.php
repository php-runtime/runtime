<?php

namespace Runtime\React;

use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Runtime\GenericRuntime;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A runtime for ReactPHP.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Runtime extends GenericRuntime
{
    private $host;
    private $port;
    private const DEFAULT_OPTIONS = [
        'host' => '127.0.0.1',
        'port' => 8080,
    ];

    public function __construct(array $options)
    {
        $options['host'] = $options['host'] ?? $_SERVER['REACT_HOST'] ?? $_ENV['REACT_HOST'] ?? self::DEFAULT_OPTIONS['host'];
        $options['port'] = $options['port'] ?? $_SERVER['REACT_PORT'] ?? $_ENV['REACT_PORT'] ?? self::DEFAULT_OPTIONS['port'];
        parent::__construct($options);
    }

    public function getRunner(?object $application): RunnerInterface
    {
        if ($application instanceof RequestHandlerInterface) {
            return new Runner($application, $this->host, $this->port);
        }

        return parent::getRunner($application);
    }
}
