<?php

namespace Runtime\React;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Socket\SocketServer;

class ServerFactory
{
    private const DEFAULT_OPTIONS = [
        'host' => '127.0.0.1',
        'port' => 8080,
    ];
    private array $options;

    public static function getDefaultOptions(): array
    {
        return self::DEFAULT_OPTIONS;
    }

    public function __construct(array $options = [])
    {
        $options['host'] = $options['host'] ?? $_SERVER['REACT_HOST'] ?? $_ENV['REACT_HOST'] ?? self::DEFAULT_OPTIONS['host'];
        $options['port'] = $options['port'] ?? $_SERVER['REACT_PORT'] ?? $_ENV['REACT_PORT'] ?? self::DEFAULT_OPTIONS['port'];

        $this->options = array_replace_recursive(self::DEFAULT_OPTIONS, $options);
    }

    public function createServer(RequestHandlerInterface $requestHandler): LoopInterface
    {
        $loop = Loop::get();
        $loop->addSignal(SIGTERM, function (int $signal) {
            exit(128 + $signal);
        });
        $loop->addSignal(SIGKILL, function (int $signal) {
            exit(128 + $signal);
        });
        $server = new HttpServer($loop, function (ServerRequestInterface $request) use ($requestHandler) {
            return $requestHandler->handle($request);
        });

        $socket = new SocketServer(sprintf('%s:%s', $this->options['host'], $this->options['port']), [], $loop);
        $server->listen($socket);

        return $loop;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
