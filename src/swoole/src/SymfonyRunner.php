<?php

namespace Runtime\Swoole;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A runner for Symfony.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyRunner implements RunnerInterface
{
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
        $this->serverFactory->createServer([$this, 'handle'])->start();

        return 0;
    }

    public function handle(Request $request, Response $response): void
    {
        // convert to HttpFoundation request
        $sfRequest = new SymfonyRequest(
            $request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            array_change_key_case($request->server ?? [], CASE_UPPER),
            $request->rawContent()
        );
        $sfRequest->headers = new HeaderBag($request->header);

        $sfResponse = $this->application->handle($sfRequest);
        foreach ($sfResponse->headers->all() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        $response->status($sfResponse->getStatusCode());
        $response->end($sfResponse->getContent());
    }
}
