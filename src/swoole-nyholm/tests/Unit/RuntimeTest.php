<?php

namespace Runtime\SwooleNyholm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Runtime\SwooleNyholm\CallableRunner;
use Runtime\SwooleNyholm\RequestHandlerRunner;
use Runtime\SwooleNyholm\Runtime;
use Symfony\Component\Runtime\Runner\ClosureRunner;

class RuntimeTest extends TestCase
{
    public function testGetRunnerCreatesARunnerForCallbacks(): void
    {
        $options = [];
        $runtime = new Runtime($options);

        $application = static function (): void {
        };
        $runner = $runtime->getRunner($application);

        self::assertInstanceOf(CallableRunner::class, $runner);
    }

    public function testGetRunnerCreatesARunnerForRequestHandlers(): void
    {
        $options = [];
        $runtime = new Runtime($options);

        $application = $this->createMock(RequestHandlerInterface::class);
        $runner = $runtime->getRunner($application);

        self::assertInstanceOf(RequestHandlerRunner::class, $runner);
    }

    public function testGetRunnerFallbacksToClosureRunner(): void
    {
        $options = [];
        $runtime = new Runtime($options);

        $runner = $runtime->getRunner(null);

        self::assertInstanceOf(ClosureRunner::class, $runner);
    }
}
