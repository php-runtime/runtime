<?php

declare(strict_types=1);

namespace Runtime\Swoole\Tests;

use PHPUnit\Framework\TestCase;
use Runtime\Swoole\CallableRunner;
use Runtime\Swoole\ServerFactory;

class CallableRunnerTest extends TestCase
{
    public function testRun(): void
    {
        $application = static function (): void {};

        $factory = $this->createMock(ServerFactory::class);
        $factory->expects(self::once())->method('createServer')->with(self::equalTo($application));

        $runner = new CallableRunner($factory, $application);

        self::assertSame(0, $runner->run());
    }
}
