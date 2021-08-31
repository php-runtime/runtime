<?php

namespace Runtime\Bref;

use Bref\Event\Handler;
use Bref\Event\Http\Psr15Handler;
use Illuminate\Contracts\Http\Kernel;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

class Runtime extends SymfonyRuntime
{
    private $handlerRunnerClass;

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
        if ($application instanceof HttpKernelInterface) {
            $application = new SymfonyHttpHandler($application);
        }

        if ($application instanceof Kernel) {
            $application = new LaravelHttpHandler($application);
        }

        if ($application instanceof RequestHandlerInterface) {
            $application = new Psr15Handler($application);
        }

        if ($application instanceof ContainerInterface) {
            $handler = explode(':', $_SERVER['_HANDLER']);
            if (!isset($handler[1]) || '' === $handler[1]) {
                throw new \RuntimeException(sprintf('Application is instance of ContainerInterface but the handler does not contain a service. The handler must be on format "path/to/file.php:App\\Lambda\\MyHandler". You provided "%s".', $_SERVER['_HANDLER']));
            }
            $application = $application->get($handler[1]);
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
