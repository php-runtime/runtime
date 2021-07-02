<?php

namespace Runtime\RoadRunnerSymfonyNyholm;

use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

/**
 * A runtime for RoadRunner.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Runtime extends SymfonyRuntime
{
    public function getRunner(?object $application): RunnerInterface
    {
        if ($application instanceof KernelInterface
            || ($application instanceof HttpCache && $application->getKernel() instanceof KernelInterface)
        ) {
            return new Runner($application);
        }

        return parent::getRunner($application);
    }
}
