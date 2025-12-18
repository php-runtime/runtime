<?php

declare(strict_types=1);

namespace Runtime\FrankenPhpSymfony;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

/**
 * A runtime for FrankenPHP.
 *
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class Runtime extends SymfonyRuntime
{
    /**
     * @param array{
     *   frankenphp_loop_max?: int,
     *   frankenphp_middlewares?: string
     * } $options
     */
    public function __construct(array $options = [])
    {
        $options['frankenphp_loop_max'] = (int) ($options['frankenphp_loop_max'] ?? $_SERVER['FRANKENPHP_LOOP_MAX'] ?? $_ENV['FRANKENPHP_LOOP_MAX'] ?? 500);
        $options['frankenphp_middlewares'] = (string) ($options['frankenphp_middlewares'] ?? $_SERVER['FRANKENPHP_MIDDLEWARES'] ?? $_ENV['FRANKENPHP_MIDDLEWARES'] ?? '');
        $options['frankenphp_middlewares'] = array_filter(explode("\n", $options['frankenphp_middlewares']));

        parent::__construct($options);
    }

    public function getRunner(?object $application): RunnerInterface
    {
        if ($application instanceof HttpKernelInterface && ($_SERVER['FRANKENPHP_WORKER'] ?? false)) {
            return new Runner($application, $this->options['frankenphp_loop_max']);
        }

        return parent::getRunner($application);
    }
}
