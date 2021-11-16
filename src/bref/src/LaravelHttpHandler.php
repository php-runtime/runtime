<?php

namespace Runtime\Bref;

use Bref\Context\Context;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

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

        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
    }

    public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
    {
        $request = Request::createFromBase(SymfonyRequestBridge::convertRequest($event, $context));
        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);
        $response->prepare($request);

        return SymfonyRequestBridge::convertResponse($response);
    }
}
