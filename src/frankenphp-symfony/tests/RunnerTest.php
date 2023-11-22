<?php

declare(strict_types=1);

namespace Runtime\FrankenPhpSymfony\Tests;

require_once __DIR__.'/function-mock.php';

use PHPUnit\Framework\TestCase;
use Runtime\FrankenPhpSymfony\Runner;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

interface TestAppInterface extends HttpKernelInterface, TerminableInterface
{
}

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class RunnerTest extends TestCase
{
    public function testRun(): void
    {
        $application = $this->createMock(TestAppInterface::class);
        $application
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function (Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response {
                $this->assertSame('bar', $request->server->get('FOO'));

                return new Response();
            });
        $application->expects($this->once())->method('terminate');

        $_SERVER['FOO'] = 'bar';

        $runner = new Runner($application, 500);
        $this->assertSame(0, $runner->run());
    }
}
