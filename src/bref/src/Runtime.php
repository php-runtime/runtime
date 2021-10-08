<?php

namespace Runtime\Bref;

use Bref\Event\Handler;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\Psr15Handler;
use Illuminate\Contracts\Http\Kernel;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Runtime\Bref\Lambda\LambdaClient;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

class Runtime extends SymfonyRuntime
{
    /**
     * @param array{
     *   bref_loop_max?: int,
     *   bref_runner_type?: string,
     *   bref_local_runner_data?: mixed,
     * } $options
     */
    public function __construct(array $options = [])
    {
        $options['bref_loop_max'] = $options['bref_loop_max'] ?? $_SERVER['BREF_LOOP_MAX'] ?? $_ENV['BREF_LOOP_MAX'] ?? 1;
        $options['bref_runner_type'] = $options['bref_runner_type'] ?? $_SERVER['BREF_RUNNER_TYPE'] ?? $_ENV['BREF_RUNNER_TYPE'] ?? 'aws';
        $options['bref_local_runner_data'] = $options['bref_local_runner_data'] ?? $_SERVER['BREF_LOCAL_RUNNER_DATA'] ?? $_ENV['BREF_LOCAL_RUNNER_DATA'] ?? [];
        parent::__construct($options);
    }

    public function getRunner(?object $application): RunnerInterface
    {
        try {
            return $this->tryToFindRunner($application);
        } catch (\Throwable $e) {
            if ('aws' === $this->options['bref_runner_type']) {
                $lambda = LambdaClient::fromEnvironmentVariable('symfony-runtime');
                $lambda->failInitialization('Could not get the Runtime runner.', $e);
            }

            throw $e;
        }
    }

    private function tryToFindRunner(?object $application)
    {
        if ($application instanceof ContainerInterface) {
            $handler = explode(':', $_SERVER['_HANDLER']);
            if (!isset($handler[1]) || '' === $handler[1]) {
                // We assume that $handler[0] is your service name, ie you are using FALLBACK_CONTAINER_FILE
                $handler[1] = $handler[0];
            }

            try {
                $application = $application->get($handler[1]);
            } catch (ServiceNotFoundException $e) {
                throw new \RuntimeException(sprintf('Application is instance of ContainerInterface but the service is not found. The handler must be on format "path/to/file.php:App\\Lambda\\MyHandler". You provided "%s".', $_SERVER['_HANDLER']), 0, $e);
            }
        }

        if ($application instanceof HttpKernelInterface) {
            if (!class_exists(HttpHandler::class)) {
                throw new \RuntimeException(sprintf('The Bref Runtime needs package bref/bref to support %s applications. Try running "composer require bref/bref".', HttpKernelInterface::class));
            }
            $application = new SymfonyHttpHandler($application);
        }

        if ($application instanceof Kernel) {
            if (!class_exists(HttpHandler::class)) {
                throw new \RuntimeException(sprintf('The Bref Runtime needs package bref/bref to support %s applications. Try running "composer require bref/bref".', Kernel::class));
            }
            $application = new LaravelHttpHandler($application);
        }

        if ($application instanceof RequestHandlerInterface) {
            if (!class_exists(Psr15Handler::class)) {
                throw new \RuntimeException(sprintf('The Bref Runtime needs package bref/bref to support %s applications. Try running "composer require bref/bref".', RequestHandlerInterface::class));
            }
            $application = new Psr15Handler($application);
        }

        if ($application instanceof Handler) {
            if ('aws' === $this->options['bref_runner_type']) {
                return new BrefRunner($application, $this->options['bref_loop_max']);
            } elseif ('local' === $this->options['bref_runner_type']) {
                return new LocalRunner($application, $this->options['bref_local_runner_data']);
            } else {
                throw new \InvalidArgumentException(sprintf('Value "%s" of "bref_runner_type" is not supported.', $this->options['bref_runner_type']));
            }
        }

        if ($application instanceof Application) {
            return new ConsoleApplicationRunner($application);
        }

        return parent::getRunner($application);
    }
}
