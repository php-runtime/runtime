<?php

declare(strict_types=1);

namespace Runtime\Swoole\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Runtime\Swoole\CallableRunner;
use Runtime\Swoole\ServerFactory;
use Runtime\Swoole\SwooleServerEventListenerInterface;
use Swoole\Http\Server;

class CallableRunnerTest extends TestCase
{
    public function testRun(): void
    {
        $eventListener = $this->createMock(SwooleServerEventListenerInterface::class);

        $application = static function (): void {
        };

        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('start');
        $server->expects(self::exactly(8))->method('on')->with($this->anything(), $this->anything());

        $factory = $this->createMock(ServerFactory::class);
        $factory->expects(self::once())->method('createServer')->with(self::equalTo($application))->willReturn($server);
        $factory->expects(self::once())->method('getOptions')->willReturn(['server_event_listener_factory' => fn () => $eventListener]);

        $runner = new CallableRunner($factory, $application);

        self::assertSame(0, $runner->run());
    }
}
