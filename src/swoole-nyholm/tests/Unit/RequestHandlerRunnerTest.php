<?php

namespace Runtime\SwooleNyholm;

use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class RequestHandlerRunnerTest extends TestCase
{
    public function testRun(): void
    {
        $server = $this->createMock(Server::class);
        $factory = $this->createMock(ServerFactory::class);
        $application = $this->createMock(RequestHandlerInterface::class);

        $factory->expects(self::once())->method('createServer')->willReturn($server);
        $server->expects(self::once())->method('start');

        $runner = new RequestHandlerRunner($factory, $application);

        self::assertSame(0, $runner->run());
    }

    public function testHandle(): void
    {
        $factory = $this->createMock(ServerFactory::class);
        $application = $this->createMock(RequestHandlerInterface::class);
        $psrResponse = $this->createMock(ResponseInterface::class);
        $psrResponseInner = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $request->expects(self::once())->method('getMethod')->willReturn('POST');
        $request->expects(self::once())->method('rawContent')->willReturn('Test');

        $application->expects(self::once())->method('handle')->willReturn($psrResponse);

        $psrResponseInner->expects(self::once())->method('getHeaders')->willReturn([
            'X-Test' => ['Swoole-Runtime'],
        ]);
        $psrResponse->expects(self::once())->method('withoutHeader')->willReturn($psrResponseInner);
        $psrResponse->expects(self::once())->method('getBody')->willReturn(Stream::create('Test'));
        $psrResponse->expects(self::once())->method('getStatusCode')->willReturn(200);
        $psrResponse->expects(self::once())->method('getReasonPhrase')->willReturn('OK');

        $response->expects(self::once())->method('setHeader')->with('X-Test', 'Swoole-Runtime');
        $response->expects(self::once())->method('write')->with('Test');
        $response->expects(self::once())->method('end')->with(null);

        $runner = new RequestHandlerRunner($factory, $application);
        $runner->handle($request, $response);
    }
}
