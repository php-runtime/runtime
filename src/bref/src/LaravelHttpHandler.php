<?php

namespace Runtime\Bref;

use Bref\Context\Context;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Illuminate\Contracts\Http\Kernel;

/**
 * A Bref handler for Laravel requests.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LaravelHttpHandler extends HttpHandler
{
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
    {
        $request = SymfonyRequestBridge::convertRequest($event, $context);
        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);
        $response->prepare($request);

        return SymfonyRequestBridge::convertResponse($response);
    }
}
