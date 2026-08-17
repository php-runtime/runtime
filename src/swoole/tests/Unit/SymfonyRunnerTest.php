<?php

declare(strict_types=1);

namespace Runtime\Swoole\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Runtime\Swoole\ServerFactory;
use Runtime\Swoole\SymfonyRunner;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SymfonyRunnerTest extends TestCase
{
    protected function tearDown(): void
    {
        // Request::$trustedProxies is a process-global static — reset it after every test so one
        // test's TRUSTED_PROXIES handling can't leak into the next.
        SymfonyRequest::setTrustedProxies([], SymfonyRequest::HEADER_X_FORWARDED_FOR);
    }

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

    public function testHandleReResolvesTrustedProxiesFromThisRequestsRemoteAddr(): void
    {
        // Simulates two requests through the same long-lived worker, from two different immediate
        // clients (e.g. two different proxy replicas) — the REMOTE_ADDR sentinel must resolve fresh
        // for each one, not freeze at whatever it was for the first request the worker ever saw.
        putenv('TRUSTED_PROXIES=REMOTE_ADDR');
        $_ENV['TRUSTED_PROXIES'] = 'REMOTE_ADDR';

        $capturedIps = [];
        $application = $this->createMock(HttpKernelInterface::class);
        $application->method('handle')
            ->willReturnCallback(function (SymfonyRequest $sfRequest) use (&$capturedIps) {
                $capturedIps[] = $sfRequest->getClientIp();

                return new SymfonyResponse('ok');
            });

        $response = $this->createMock(Response::class);
        $factory = $this->createMock(ServerFactory::class);
        $runner = new SymfonyRunner($factory, $application);

        $firstRequest = $this->createMock(Request::class);
        $firstRequest->server = ['remote_addr' => '10.0.0.1'];
        $firstRequest->header = ['x-forwarded-for' => '203.0.113.1'];
        $runner->handle($firstRequest, $response);

        $secondRequest = $this->createMock(Request::class);
        $secondRequest->server = ['remote_addr' => '10.0.0.2'];
        $secondRequest->header = ['x-forwarded-for' => '203.0.113.2'];
        $runner->handle($secondRequest, $response);

        // Each request is trusted as coming from its OWN immediate proxy, so getClientIp() resolves
        // to the forwarded IP both times — not just the first, and not the raw proxy IP either.
        self::assertSame(['203.0.113.1', '203.0.113.2'], $capturedIps);

        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES']);
    }

    public function testHandleLeavesTrustedProxiesUntouchedWhenEnvVarIsUnset(): void
    {
        putenv('TRUSTED_PROXIES');
        unset($_ENV['TRUSTED_PROXIES']);

        SymfonyRequest::setTrustedProxies(['198.51.100.1'], SymfonyRequest::HEADER_X_FORWARDED_FOR);

        $application = $this->createMock(HttpKernelInterface::class);
        $application->method('handle')->willReturn(new SymfonyResponse('ok'));

        $response = $this->createMock(Response::class);
        $request = $this->createMock(Request::class);
        $request->server = ['remote_addr' => '10.0.0.1'];
        $factory = $this->createMock(ServerFactory::class);

        $runner = new SymfonyRunner($factory, $application);
        $runner->handle($request, $response);

        self::assertSame(['198.51.100.1'], SymfonyRequest::getTrustedProxies());
    }
}
