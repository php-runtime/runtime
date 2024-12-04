<?php

declare(strict_types=1);

namespace Runtime\FrankenPhpSymfony\Tests;

use PHPUnit\Framework\TestCase;
use Runtime\FrankenPhpSymfony\ResponseRunner;
use Runtime\FrankenPhpSymfony\Runner;
use Runtime\FrankenPhpSymfony\Runtime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
final class RuntimeTest extends TestCase
{
    public function testGetRunner(): void
    {
        unset($_SERVER['FRANKENPHP_WORKER']);
        $application = $this->createStub(HttpKernelInterface::class);

        $runtime = new Runtime();
        $this->assertNotInstanceOf(Runner::class, $runtime->getRunner(null));
        $this->assertNotInstanceOf(Runner::class, $runtime->getRunner($application));

        $_SERVER['FRANKENPHP_WORKER'] = 1;
        $this->assertInstanceOf(Runner::class, $runtime->getRunner($application));
    }

    public function testGetResponseRunner(): void
    {
        unset($_SERVER['FRANKENPHP_WORKER']);
        $application = $this->createStub(Response::class);

        $runtime = new Runtime();
        $this->assertNotInstanceOf(ResponseRunner::class, $runtime->getRunner(null));
        $this->assertNotInstanceOf(ResponseRunner::class, $runtime->getRunner($application));

        $_SERVER['FRANKENPHP_WORKER'] = 1;
        $this->assertInstanceOf(ResponseRunner::class, $runtime->getRunner($application));
    }
}
