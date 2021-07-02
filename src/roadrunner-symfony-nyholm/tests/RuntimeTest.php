<?php

namespace Runtime\RoadRunnerSymfonyNyholm;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\Runner\Symfony\HttpKernelRunner;

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

        $kernel->expects($this->once())
            ->method('boot');

        $container = $this->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('getParameter')
            ->with('session.storage.options')
            ->will($this->returnValue([]));

        $kernel->expects($this->once())
            ->method('getContainer')
            ->will($this->returnValue($container));

        $kernel->expects($this->once())
            ->method('shutdown');

        $runtime = new Runtime();
        $runner = $runtime->getRunner($kernel);

        $this->assertInstanceOf(Runner::class, $runner);
    }

    public function testGetRuntimeHttpCache(): void
    {
        $httpCache = $this->getMockBuilder(HttpCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $kernel = $this->getMockBuilder(KernelInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $httpCache->expects($this->atLeastOnce())
            ->method('getKernel')
            ->will($this->returnValue($kernel));

        $kernel->expects($this->once())
            ->method('boot');

        $container = $this->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('getParameter')
            ->with('session.storage.options')
            ->will($this->returnValue([]));

        $kernel->expects($this->once())
            ->method('getContainer')
            ->will($this->returnValue($container));

        $kernel->expects($this->once())
            ->method('shutdown');

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

        $this->assertInstanceOf(HttpKernelRunner::class, $runner);
    }
}
