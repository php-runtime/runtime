<?php

namespace Runtime\SwooleNyholm;

use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Runtime\RunnerInterface;

class RequestHandlerRunner implements RunnerInterface
{
    private const CHUNK_SIZE = 2097152; // 2MB

    /**
     * @var ServerFactory
     */
    private $serverFactory;

    /**
     * @var RequestHandlerInterface
     */
    private $application;

    public function __construct(ServerFactory $serverFactory, RequestHandlerInterface $application)
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
        $psrRequest = (new \Nyholm\Psr7\ServerRequest(
            $request->getMethod(),
            $request->server['request_uri'] ?? '/',
            $request->header ?? [],
            $request->rawContent(),
            '1.1',
            $request->server ?? []
        ))
            ->withQueryParams($request->get ?? []);

        $psrResponse = $this->application->handle($psrRequest);

        $response->setStatusCode($psrResponse->getStatusCode(), $psrResponse->getReasonPhrase());

        foreach ($psrResponse->withoutHeader('set-cookies')->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $response->setHeader($name, $value);
            }
        }

        if ($psrResponse->hasHeader('set-cookies')) {
            foreach ($psrResponse->getHeader('set-cookies') as $cookieString) {
                $cookie = Cookie::fromString($cookieString);
                $response->cookie(
                    $cookie->getName(),
                    $cookie->getValue() ?? '',
                    $cookie->getExpiresTime(),
                    $cookie->getPath(),
                    $cookie->getDomain() ?? '',
                    $cookie->isSecure(),
                    $cookie->isHttpOnly(),
                    $cookie->getSameSite() ?? ''
                );
            }
        }

        $body = $psrResponse->getBody();
        $body->rewind();

        if ($body->isReadable()) {
            if ($body->getSize() <= self::CHUNK_SIZE) {
                if ($contents = $body->getContents()) {
                    $response->write($contents);
                }
            } else {
                while (!$body->eof() && ($contents = $body->read(self::CHUNK_SIZE))) {
                    $response->write($contents);
                }
            }

            $response->end();
        } else {
            $response->end((string) $body);
        }

        $body->close();
    }
}
