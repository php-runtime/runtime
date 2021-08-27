<?php

declare(strict_types=1);

namespace Runtime\Swoole\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Runtime\Swoole\ServerFactory;
use Runtime\Swoole\SymfonyRunner;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SymfonyRunnerTest extends TestCase
{
    public function testRun(): void
    {
        $application = $this->createMock(HttpKernelInterface::class);

        $server = $this->createMock(Server::class);
        $server->expects(self::once())->method('start');

        $factory = $this->createMock(ServerFactory::class);
        $factory->expects(self::once())->method('createServer')->willReturn($server);

        $runner = new SymfonyRunner($factory, $application);

        self::assertSame(0, $runner->run());
    }

    public function testHandle(): void
    {
        $sfResponse = $this->createMock(\Symfony\Component\HttpFoundation\Response::class);
        $sfResponse->headers = new HeaderBag(['X-Test' => 'Swoole-Runtime']);
        $sfResponse->expects(self::once())->method('getStatusCode')->willReturn(201);
        $sfResponse->expects(self::once())->method('getContent')->willReturn('Test');

        $application = $this->createMock(HttpKernelInterface::class);
        $application->expects(self::once())->method('handle')->willReturn($sfResponse);

        $request = $this->createMock(Request::class);
        $request->header = [];

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('header')->with('x-test', 'Swoole-Runtime');
        $response->expects(self::once())->method('status')->with(201);
        $response->expects(self::once())->method('end')->with('Test');

        $factory = $this->createMock(ServerFactory::class);

        $runner = new SymfonyRunner($factory, $application);
        $runner->handle($request, $response);
    }
}
