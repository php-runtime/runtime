<?php

declare(strict_types=1);

namespace Runtime\Swoole\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Runtime\Swoole\CallableRunner;
use Runtime\Swoole\ServerFactory;
use Swoole\Http\Server;

class CallableRunnerTest extends TestCase
{
    public function testRun(): void
    {
        $application = static function (): void {
        };

        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('start');

        $factory = $this->createMock(ServerFactory::class);
        $factory->expects(self::once())->method('createServer')->with(self::equalTo($application))->willReturn($server);

        $runner = new CallableRunner($factory, $application);

        self::assertSame(0, $runner->run());
    }
}
