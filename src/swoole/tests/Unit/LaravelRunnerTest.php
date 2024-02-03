<?php

declare(strict_types=1);

namespace Runtime\Swoole\Tests\Unit;

use Illuminate\Contracts\Http\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Runtime\Swoole\LaravelRunner;
use Runtime\Swoole\ServerFactory;
use Runtime\Swoole\SwooleServerEventListenerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LaravelRunnerTest extends TestCase
{
    public function testRun(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $eventListener = $this->createMock(SwooleServerEventListenerInterface::class);

        $application = $this->createMock(Kernel::class);
        $application->expects(self::once())->method('getApplication')->willReturn($container);

        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('start');
        $server->expects(self::exactly(8))->method('on')->with($this->anything(), $this->anything());

        $factory = $this->createMock(ServerFactory::class);
        $factory->expects(self::once())->method('createServer')->willReturn($server);
        $factory->expects(self::once())->method('getOptions')->willReturn(['server_event_listener_factory' => fn () => $eventListener]);

        $runner = new LaravelRunner($factory, $application);

        self::assertSame(0, $runner->run());
    }

    public function testHandle(): void
    {
        $sfResponse = new SymfonyResponse('foo');

        $application = $this->createMock(Kernel::class);
        $application->expects(self::once())->method('handle')->willReturn($sfResponse);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('end')->with('foo');

        $request = $this->createMock(Request::class);
        $factory = $this->createMock(ServerFactory::class);

        $runner = new LaravelRunner($factory, $application);
        $runner->handle($request, $response);
    }
}
