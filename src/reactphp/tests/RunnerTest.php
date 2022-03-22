<?php

namespace Runtime\React\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use Runtime\React\Runner;
use Runtime\React\ServerFactory;

class RunnerTest extends TestCase
{
    public function testRun(): void
    {
        $handler = function () {};
        $loop = $this->createMock(LoopInterface::class);
        Loop::set($loop);
        $server = new HttpServer($handler); //final, cannot be mocked
        $factory = $this->createMock(ServerFactory::class);
        $application = $this->createMock(RequestHandlerInterface::class);

        $factory->expects(self::once())->method('createServer')->willReturn($loop);

        $runner = new Runner($factory, $application);

        self::assertSame(0, $runner->run());
    }
}
