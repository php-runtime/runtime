<?php

namespace Runtime\Swoole;

use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\Runtime\RunnerInterface;

class RequestHandlerRunner implements RunnerInterface
{
    /**
     * @var int
     */
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
            array_change_key_case($request->server ?? [], CASE_UPPER),
            $request->rawContent(),
            '1.1',
            $request->server ?? []
        ))
            ->withQueryParams($request->get ?? []);

        $psrResponse = $this->application->handle($psrRequest);

        $response->setStatusCode($psrResponse->getStatusCode(), $psrResponse->getReasonPhrase());

        foreach ($psrResponse->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $response->setHeader($name, $value);
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
