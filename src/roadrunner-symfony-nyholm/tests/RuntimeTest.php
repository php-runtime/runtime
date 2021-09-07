<?php

namespace Runtime\RoadRunnerSymfonyNyholm;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\Runner\Symfony\ConsoleApplicationRunner;

/**
 * @author Alexander Schranz <alexander@sulu.io>
 */
class RuntimeTest extends TestCase
{
    public function testGetRuntimeHttpKernel(): void
    {
        $kernel = $this->getMockBuilder(KernelInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $runtime = new Runtime();
        $runner = $runtime->getRunner($kernel);

        $this->assertInstanceOf(Runner::class, $runner);
    }

    public function testGetRuntimeHttpCache(): void
    {
        $httpCache = $this->getMockBuilder(HttpCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $runtime = new Runtime();
        $runner = $runtime->getRunner($httpCache);

        $this->assertInstanceOf(Runner::class, $runner);
    }

    public function testGetRuntimeHttpKernelInterface(): void
    {
        $kernel = $this->getMockBuilder(HttpKernelInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $runtime = new Runtime();
        $runner = $runtime->getRunner($kernel);

        $this->assertInstanceOf(Runner::class, $runner);
    }

    public function testGetRuntimeApplication(): void
    {
        $kernel = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->getMock();

        $runtime = new Runtime();
        $runner = $runtime->getRunner($kernel);

        $this->assertInstanceOf(ConsoleApplicationRunner::class, $runner);
    }
}
