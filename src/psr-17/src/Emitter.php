<?php

namespace Runtime\Psr17;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Emitter implements RunnerInterface
{
    private $requestHandler;
    private $response;
    private $request;

    private function __construct()
    {
    }

    public static function createForResponse(ResponseInterface $response): self
    {
        $self = new self();
        $self->response = $response;

        return $self;
    }

    public static function createForRequestHandler(RequestHandlerInterface $handler, ServerRequestInterface $request): self
    {
        $self = new self();
        $self->requestHandler = $handler;
        $self->request = $request;

        return $self;
    }

    public function run(): int
    {
        if (null === $this->response) {
            $this->response = $this->requestHandler->handle($this->request);
        }

        $this->emit($this->response);

        return 0;
    }

    /**
     * Emits a response for a PHP SAPI environment.
     *
     * Emits the status line and headers via the header() function, and the
     * body content via the output buffer.
     *
     * @Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC.
     */
    public function emit(ResponseInterface $response): void
    {
        if (headers_sent()) {
            throw EmitterException::forHeadersSent();
        }

        if (ob_get_level() > 0 && ob_get_length() > 0) {
            throw EmitterException::forOutputSent();
        }

        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        echo $response->getBody();
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is available, it, too, is emitted.
     *
     * It is important to mention that this method should be called after
     * `emitHeaders()` in order to prevent PHP from changing the status code of
     * the emitted response.
     *
     * @Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC.
     */
    private function emitStatusLine(ResponseInterface $response): void
    {
        $reasonPhrase = $response->getReasonPhrase();
        $statusCode = $response->getStatusCode();

        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $statusCode,
            ($reasonPhrase ? ' '.$reasonPhrase : '')
        ), true, $statusCode);
    }

    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value
     * is an array with multiple values, ensures that each is sent
     * in such a way as to create aggregate headers (instead of replace
     * the previous).
     *
     * @Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC.
     */
    private function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            $name = ucwords($header, '-');
            $first = 'Set-Cookie' === $name ? false : true;
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $first, $statusCode);
                $first = false;
            }
        }
    }
}
