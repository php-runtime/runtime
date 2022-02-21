<?php

namespace Runtime\Swoole\Tests\Unit;

use Illuminate\Contracts\Http\Kernel;
use PHPUnit\Framework\TestCase;
use Runtime\Swoole\CallableRunner;
use Runtime\Swoole\LaravelRunner;
use Runtime\Swoole\Runtime;
use Runtime\Swoole\SymfonyRunner;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\Runner\ClosureRunner;

class RuntimeTest extends TestCase
{
    public function testGetRunnerCreatesARunnerForCallbacks(): void
    {
        $options = ['error_handler' => false];
        $runtime = new Runtime($options);

        $application = static function (): void {
        };
        $runner = $runtime->getRunner($application);

        self::assertInstanceOf(CallableRunner::class, $runner);
    }

    public function testGetRunnerCreatesARunnerForSymfony(): void
    {
        $options = ['error_handler' => false];
        $runtime = new Runtime($options);

        $application = $this->createMock(HttpKernelInterface::class);
        $runner = $runtime->getRunner($application);

        self::assertInstanceOf(SymfonyRunner::class, $runner);
    }

    public function testGetRunnerCreatesARunnerForLaravel(): void
    {
        $options = ['error_handler' => false];
        $runtime = new Runtime($options);

        $application = $this->createMock(Kernel::class);
        $runner = $runtime->getRunner($application);

        self::assertInstanceOf(LaravelRunner::class, $runner);
    }

    public function testGetRunnerFallbacksToClosureRunner(): void
    {
        $options = ['error_handler' => false];
        $runtime = new Runtime($options);

        $runner = $runtime->getRunner(null);

        self::assertInstanceOf(ClosureRunner::class, $runner);
    }
}
