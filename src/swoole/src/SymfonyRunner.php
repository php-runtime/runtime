<?php

namespace Runtime\Swoole;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A runner for Symfony.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyRunner implements RunnerInterface
{
    use SwooleEventsTrait;

    /** @var ServerFactory */
    private $serverFactory;
    /** @var HttpKernelInterface */
    private $application;

    public function __construct(ServerFactory $serverFactory, HttpKernelInterface $application)
    {
        $this->serverFactory = $serverFactory;
        $this->application = $application;
    }

    public function run(): int
    {
        $server = $this->serverFactory->createServer([$this, 'handle']);

        if ($this->application instanceof KernelInterface) {
            $this->registerSwooleEvents($server, $this->serverFactory->getOptions(), $this->application->getContainer());
        }

        $server->start();

        return 0;
    }

    public function handle(Request $request, Response $response): void
    {
        $sfRequest = SymfonyHttpBridge::convertSwooleRequest($request);

        $sfResponse = $this->application->handle($sfRequest);
        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $response);

        if ($this->application instanceof TerminableInterface) {
            $this->application->terminate($sfRequest, $sfResponse);
        }
    }
}
