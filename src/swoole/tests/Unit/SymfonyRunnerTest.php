<?php

declare(strict_types=1);

namespace Runtime\Swoole\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Runtime\Swoole\ServerFactory;
use Runtime\Swoole\SymfonyRunner;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        $sfResponse = new SymfonyResponse('foo');

        $application = $this->createMock(HttpKernelInterface::class);
        $application->expects(self::once())->method('handle')->willReturn($sfResponse);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('end')->with('foo');

        $request = $this->createMock(Request::class);
        $factory = $this->createMock(ServerFactory::class);

        $runner = new SymfonyRunner($factory, $application);
        $runner->handle($request, $response);
    }

    public function testHandleHonoursTheServerFactorysStreamedResponseChunkSize(): void
    {
        // A chunk size of 1 forces a write() on every echo, regardless of its length — the setting
        // this test exists to prove reaches SymfonyHttpBridge from ServerFactory's options.
        $sfResponse = new StreamedResponse(static function () {
            echo 'Foo';
            echo 'Bar';
        });

        $application = $this->createMock(HttpKernelInterface::class);
        $application->expects(self::once())->method('handle')->willReturn($sfResponse);

        $response = $this->createMock(Response::class);
        $expectedWrites = ['Foo', 'Bar'];
        $callCount = 0;
        $response->expects(self::exactly(2))->method('write')
            ->willReturnCallback(function ($string) use ($expectedWrites, &$callCount) {
                $this->assertEquals($expectedWrites[$callCount], $string);
                ++$callCount;

                return true;
            });
        $response->expects(self::once())->method('end');

        $request = $this->createMock(Request::class);
        $factory = $this->createMock(ServerFactory::class);
        $factory->method('getOptions')->willReturn(['streamed_response_chunk_size' => 1]);

        $runner = new SymfonyRunner($factory, $application);
        $runner->handle($request, $response);
    }
}
