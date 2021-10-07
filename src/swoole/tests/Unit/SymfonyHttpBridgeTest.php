<?php

namespace Runtime\Swoole\Tests\Unit;

use Runtime\Swoole\SymfonyHttpBridge;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Response;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
class SymfonyHttpBridgeTest extends TestCase
{
    public function testThatSymfonyResponseIsReflected(): void
    {
        $sfResponse = $this->createMock(SymfonyResponse::class);
        $sfResponse->headers = new HeaderBag(['X-Test' => 'Swoole-Runtime']);
        $sfResponse->expects(self::once())->method('getStatusCode')->willReturn(201);
        $sfResponse->expects(self::once())->method('getContent')->willReturn('Test');

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('header')->with('x-test', 'Swoole-Runtime');
        $response->expects(self::once())->method('status')->with(201);
        $response->expects(self::once())->method('end')->with('Test');

        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $response);
    }
}
