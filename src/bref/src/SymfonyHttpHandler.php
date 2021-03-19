<?php

namespace Runtime\Bref;

use Bref\Context\Context;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * A Bref handler for Symfony requests.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyHttpHandler extends HttpHandler
{
    private $kernel;

    public function __construct(HttpKernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        $server = [
            'SERVER_PROTOCOL' => $event->getProtocolVersion(),
            'REQUEST_METHOD' => $event->getMethod(),
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'QUERY_STRING' => $event->getQueryString(),
            'DOCUMENT_ROOT' => getcwd(),
            'REQUEST_URI' => $event->getUri(),
        ];

        foreach ($event->getHeaders() as $name => $values) {
            $server['HTTP_'.strtoupper($name)] = $values[0];
        }

        // TODO convert request better
        $request = Request::create(
            $event->getUri(),
            $event->getMethod(),
            [],
            [],
            [],
            $server,
            $event->getBody()
        );

        $response = $this->kernel->handle($request);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }

        return new HttpResponse($response->getContent(), $response->headers->all(), $response->getStatusCode());
    }
}
