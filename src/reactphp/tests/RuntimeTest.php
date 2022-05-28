<?php

namespace Runtime\React\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Runtime\React\Runner;
use Runtime\React\Runtime;

class RuntimeTest extends TestCase
{
    public function testGetRunnerCreatesARunnerForRequestHandlers(): void
    {
        $options = [];
        $runtime = new Runtime($options);

        $application = $this->createMock(RequestHandlerInterface::class);
        $runner = $runtime->getRunner($application);

        self::assertInstanceOf(Runner::class, $runner);
    }
}
