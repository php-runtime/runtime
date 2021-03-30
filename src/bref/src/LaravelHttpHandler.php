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
    }

    public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);

        // CGI Version 1.1 - Section 4.1
        $server = array_filter([
            'AUTH_TYPE' => $event->getHeaders()['auth-type'] ?? null, // 4.1.1
            'CONTENT_LENGTH' => $event->getHeaders()['content-length'] ?? null, // 4.1.2
            'CONTENT_TYPE' => $event->getContentType(), // 4.1.3
            'QUERY_STRING' => $event->getQueryString(), // 4.1.7
            'REQUEST_METHOD' => $event->getMethod(), // 4.1.12
            'SERVER_PORT' => $event->getServerPort(), // 4.1.16
            'SERVER_PROTOCOL' => $event->getProtocolVersion(), // 4.1.16
            'DOCUMENT_ROOT' => getcwd(),
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'REQUEST_URI' => $event->getUri(),
        ], fn ($value) => null !== $value);

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
        $this->kernel->terminate($request, $response);
        $response->prepare($request);

        return new HttpResponse($response->getContent(), $response->headers->all(), $response->getStatusCode());
    }
}
