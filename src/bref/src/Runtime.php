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
    /**
     * @param array{
     *   bref_loop_max?: int,
     * } $options
     */
    public function __construct(array $options = [])
    {
        $options['bref_loop_max'] = $options['bref_loop_max'] ?? $_SERVER['BREF_LOOP_MAX'] ?? $_ENV['BREF_LOOP_MAX'] ?? 1;
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
            return new BrefRunner($application, $this->options['bref_loop_max']);
        }

        if ($application instanceof Application) {
            return new ConsoleApplicationRunner($application);
        }

        return parent::getRunner($application);
    }
}
