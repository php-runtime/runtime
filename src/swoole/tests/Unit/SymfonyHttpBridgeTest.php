<?php

namespace Runtime\Swoole\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Runtime\Swoole\SymfonyHttpBridge;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
class SymfonyHttpBridgeTest extends TestCase
{
    public function testThatSwooleRequestIsConverted(): void
    {
        $request = $this->createMock(Request::class);
        $request->server = ['request_method' => 'post'];
        $request->header = ['content-type' => 'application/json'];
        $request->cookie = ['foo' => 'cookie'];
        $request->get = ['foo' => 'get'];
        $request->post = ['foo' => 'post'];
        $request->files = [
            'foo' => [
                'name' => 'file',
                'type' => 'image/png',
                'tmp_name' => '/tmp/file',
                'error' => UPLOAD_ERR_CANT_WRITE,
                'size' => 0,
            ],
        ];
        $request->expects(self::once())->method('rawContent')->willReturn('{"foo": "body"}');

        $sfRequest = SymfonyHttpBridge::convertSwooleRequest($request);

        $this->assertSame(['REQUEST_METHOD' => 'post'], $sfRequest->server->all());
        $this->assertSame(['content-type' => ['application/json']], $sfRequest->headers->all());
        $this->assertSame(['foo' => 'cookie'], $sfRequest->cookies->all());
        $this->assertSame(['foo' => 'get'], $sfRequest->query->all());
        $this->assertSame(['foo' => 'post'], $sfRequest->request->all());
        $this->assertEquals('{"foo": "body"}', $sfRequest->getContent());

        $this->assertCount(1, $sfRequest->files);

        /** @var UploadedFile $file */
        $file = $sfRequest->files->get('foo');
        $this->assertNotNull($file);
        $this->assertEquals('file', $file->getClientOriginalName());
        $this->assertEquals('image/png', $file->getClientMimeType());
        $this->assertEquals('/tmp/file', $file->getPathname());
        $this->assertEquals(UPLOAD_ERR_CANT_WRITE, $file->getError());
    }

    public function testThatSymfonyResponseIsReflected(): void
    {
        $fooCookie = (string) new Cookie('foo', '123');
        $barCookie = (string) new Cookie('bar', '234');

        $sfResponse = $this->createMock(SymfonyResponse::class);
        $sfResponse->headers = new ResponseHeaderBag([
            'X-Test' => 'Swoole-Runtime',
            'Set-Cookie' => [$fooCookie, $barCookie],
            'Cache-Control' => 'no-cache, private',
            'Date' => 'Thu, 25 Dec 2025 19:20:06 GMT',
        ]);
        $sfResponse->expects(self::once())->method('getStatusCode')->willReturn(201);
        $sfResponse->expects(self::once())->method('getContent')->willReturn('Test');

        $response = $this->createMock(Response::class);
        $expectedHeaders = [
            ['X-Test', 'Swoole-Runtime'],
            ['Cache-Control', 'no-cache, private'],
            ['Date', 'Thu, 25 Dec 2025 19:20:06 GMT'],
        ];
        $callCount = 0;
        $response->expects(self::exactly(3))->method('header')
            ->willReturnCallback(function ($key, $value) use ($expectedHeaders, &$callCount) {
                $this->assertArrayHasKey($callCount, $expectedHeaders);
                $this->assertEquals($expectedHeaders[$callCount][0], $key);
                $this->assertEquals($expectedHeaders[$callCount][1], $value);
                ++$callCount;

                return true;
            });
        $expectedCookies = [
            ['foo', '123'],
            ['bar', '234'],
        ];
        $callCountCookie = 0;
        $response->expects(self::exactly(2))->method('cookie')
            ->willReturnCallback(function ($name, $value) use ($expectedCookies, &$callCountCookie) {
                $this->assertArrayHasKey($callCountCookie, $expectedCookies);
                $this->assertEquals($expectedCookies[$callCountCookie][0], $name);
                $this->assertEquals($expectedCookies[$callCountCookie][1], $value);
                ++$callCountCookie;

                return true;
            });
        $response->expects(self::once())->method('status')->with(201);
        $response->expects(self::once())->method('end')->with('Test');

        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $response);
    }

    public function testThatSymfonyStreamedResponseIsReflected(): void
    {
        $sfResponse = new StreamedResponse(function () {
            echo "Foo\n";
            ob_flush();

            echo "Bar\n";
            ob_flush();
        });

        $response = $this->createMock(Response::class);
        $expectedWrites = [
            "Foo\n",
            "Bar\n",
            '',
        ];
        $callCount = 0;
        $response->expects(self::exactly(3))->method('write')
            ->willReturnCallback(function ($string) use ($expectedWrites, &$callCount) {
                $this->assertEquals($expectedWrites[$callCount], $string);
                ++$callCount;

                return true;
            });
        $response->expects(self::once())->method('end');

        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $response);
    }

    public function testThatSymfonyBinaryFileResponseIsReflected(): void
    {
        $file = tempnam(sys_get_temp_dir(), uniqid());
        file_put_contents($file, 'Foo');

        $sfResponse = new BinaryFileResponse($file);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('sendfile')->with($file, null, null);

        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $response);
    }

    public function testThatSymfonyBinaryFileResponseWithRangeIsReflected(): void
    {
        $file = tempnam(sys_get_temp_dir(), uniqid());
        file_put_contents($file, 'FooBar');

        $request = new SymfonyRequest();
        $request->headers->set('Range', 'bytes=2-4');

        $sfResponse = new BinaryFileResponse($file);
        $sfResponse->prepare($request);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('write')->with('oBa');
        $response->expects(self::once())->method('end');

        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $response);
    }

    public function testStreamedResponseWillRespondWithOneChunkAtATime(): void
    {
        $sfResponse = new StreamedResponse(static function () {
            echo str_repeat('a', 4096);
            echo str_repeat('b', 4095);
        });

        $response = $this->createMock(Response::class);
        $response->expects(self::exactly(2))
            ->method('write')
            ->with(self::logicalOr(
                str_repeat('a', 4096),
                str_repeat('b', 4095)
            ));
        $response->expects(self::once())->method('end');

        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $response);
    }
}
