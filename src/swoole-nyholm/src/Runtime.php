<?php

namespace Runtime\SwooleNyholm;

use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

/**
 * A runtime for Swoole.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Runtime extends SymfonyRuntime
{
    private ?ServerFactory $serverFactory;

    public function __construct(array $options, ?ServerFactory $serverFactory = null)
    {
        $this->serverFactory = $serverFactory ?? new ServerFactory($options);
        parent::__construct($this->serverFactory->getOptions());
    }

    public function getRunner(?object $application): RunnerInterface
    {
        if (is_callable($application)) {
            return new CallableRunner($this->serverFactory, $application);
        }

        if ($application instanceof RequestHandlerInterface) {
            return new RequestHandlerRunner($this->serverFactory, $application);
        }

        return parent::getRunner($application);
    }
}
