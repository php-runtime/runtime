<?php

namespace Runtime\Swoole\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Runtime\Swoole\ServerFactory;

/**
 * @runTestsInSeparateProcesses Swoole finds conflicting servers even without start()
 */
class ServerFactoryTest extends TestCase
{
    public function testCreateServerWithDefaultOptions(): void
    {
        $factory = new ServerFactory();
        $server = $factory->createServer(static function (): void {
        });
        $defaults = ServerFactory::getDefaultOptions();

        self::assertSame($defaults['host'], $server->host);
        self::assertSame($defaults['port'], $server->port);
        self::assertSame($defaults['mode'], $server->mode);
        self::assertSame($defaults['sock_type'], $server->type);
        self::assertSame($defaults['settings'], $server->setting);
    }

    public function testCreateServerWithGivenOptions(): void
    {
        $options = [
            'host' => '0.0.0.0',
            'port' => 9501,
            'mode' => 1,
            'sock_type' => 2,
            'settings' => [
                'worker_num' => 1,
            ],
        ];

        $factory = new ServerFactory($options);
        $server = $factory->createServer(static function (): void {
        });

        self::assertSame('0.0.0.0', $server->host);
        self::assertSame(9501, $server->port);
        self::assertSame(1, $server->mode);
        self::assertSame(2, $server->type);
        self::assertSame(['worker_num' => 1], $server->setting);
    }

    public function testCreateServerWithPartialOptionsOverride(): void
    {
        $options = [
            'mode' => 1,
            'sock_type' => 2,
            'settings' => [
                'worker_num' => 1,
            ],
        ];

        $factory = new ServerFactory($options);
        $server = $factory->createServer(static function (): void {
        });
        $defaults = ServerFactory::getDefaultOptions();

        self::assertSame($defaults['host'], $server->host);
        self::assertSame($defaults['port'], $server->port);
        self::assertSame(1, $server->mode);
        self::assertSame(2, $server->type);
        self::assertSame(['worker_num' => 1], $server->setting);
    }
}
