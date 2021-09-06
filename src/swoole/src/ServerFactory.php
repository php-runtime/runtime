<?php

namespace Runtime\Swoole;

use Swoole\Http\Server;

/**
 * A factory for Swoole HTTP Servers.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ServerFactory
{
    private const DEFAULT_OPTIONS = [
        'host' => '127.0.0.1',
        'port' => 8000,
        'mode' => 2, // SWOOLE_PROCESS
        'sock_type' => 1, // SWOOLE_SOCK_TCP
        'settings' => [],
    ];

    /** @var array */
    private $options;

    public static function getDefaultOptions(): array
    {
        return self::DEFAULT_OPTIONS;
    }

    public function __construct(array $options = [])
    {
        $options['host'] = $options['host'] ?? $_SERVER['SWOOLE_HOST'] ?? $_ENV['SWOOLE_HOST'] ?? self::DEFAULT_OPTIONS['host'];
        $options['port'] = $options['port'] ?? $_SERVER['SWOOLE_PORT'] ?? $_ENV['SWOOLE_PORT'] ?? self::DEFAULT_OPTIONS['port'];
        $options['mode'] = $options['mode'] ?? $_SERVER['SWOOLE_MODE'] ?? $_ENV['SWOOLE_MODE'] ?? self::DEFAULT_OPTIONS['mode'];
        $options['sock_type'] = $options['sock_type'] ?? $_SERVER['SWOOLE_SOCK_TYPE'] ?? $_ENV['SWOOLE_SOCK_TYPE'] ?? self::DEFAULT_OPTIONS['sock_type'];

        $this->options = array_replace_recursive(self::DEFAULT_OPTIONS, $options);
    }

    public function createServer(callable $requestHandler): Server
    {
        $server = new Server($this->options['host'], (int) $this->options['port'], (int) $this->options['mode'], (int) $this->options['sock_type']);
        $server->set($this->options['settings']);
        $server->on('request', $requestHandler);

        return $server;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
