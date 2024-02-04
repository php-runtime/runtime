<?php

namespace Runtime\Swoole;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request as LaravelRequest;
use Psr\Container\ContainerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A runner for Laravel.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LaravelRunner implements RunnerInterface
{
    use SwooleEventsTrait;

    /** @var ServerFactory */
    private $serverFactory;
    /** @var Kernel */
    private $application;

    public function __construct(ServerFactory $serverFactory, Kernel $application)
    {
        $this->serverFactory = $serverFactory;
        $this->application = $application;
    }

    public function run(): int
    {
        $server = $this->serverFactory->createServer([$this, 'handle']);

        if (($container = $this->application->getApplication()) instanceof ContainerInterface) {
            $this->registerSwooleEvents($server, $this->serverFactory->getOptions(), $container);
        }

        $server->start();

        return 0;
    }

    public function handle(Request $request, Response $response): void
    {
        $sfRequest = LaravelRequest::createFromBase(SymfonyHttpBridge::convertSwooleRequest($request));

        $sfResponse = $this->application->handle($sfRequest);
        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $response);

        $this->application->terminate($sfRequest, $sfResponse);
    }
}
