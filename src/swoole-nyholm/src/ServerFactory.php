<?php

namespace Runtime\SwooleNyholm;

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

        $this->options = array_replace_recursive(self::DEFAULT_OPTIONS, $options);
    }

    public function createServer(callable $requestHandler): Server
    {
        $server = new Server($this->options['host'], (int) $this->options['port'], (int) $this->options['mode']);
        $server->set($this->options['settings']);
        $server->on('request', $requestHandler);

        return $server;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
