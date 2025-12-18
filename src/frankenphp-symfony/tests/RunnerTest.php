<?php

declare(strict_types=1);

namespace Runtime\FrankenPhpSymfony\Tests;

require_once __DIR__.'/function-mock.php';

use PHPUnit\Framework\TestCase;
use Runtime\FrankenPhpSymfony\Exception\InvalidMiddlewareException;
use Runtime\FrankenPhpSymfony\Runner;
use Runtime\FrankenPhpSymfony\Tests\Support\InvalidMiddleware;
use Runtime\FrankenPhpSymfony\Tests\Support\TestMiddleware;
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
    public static function runData(): iterable
    {
        yield 'basic' => [];

        yield 'middleware' => [
            'middleware' => TestMiddleware::class,
        ];

        yield 'Invalid middleware' => [
            'middleware' => InvalidMiddleware::class,
            'expectException' => InvalidMiddlewareException::class,
        ];
    }

    /**
     * @dataProvider runData
     */
    public function testRun(
        ?string $middleware = null,
        ?string $expectException = null,
    ): void {
        $application = $this->createMock(TestAppInterface::class);

        if (null === $expectException) {
            $application
                ->expects($this->once())
                ->method('handle')
                ->willReturnCallback(
                    function (
                        Request $request,
                        int $type = HttpKernelInterface::MAIN_REQUEST,
                        bool $catch = true
                    ): Response {
                        $this->assertSame('bar', $request->server->get('FOO'));

                        return new Response();
                    }
                );
            $application->expects($this->once())->method('terminate');
        } else {
            $this->expectException($expectException);
        }

        $_SERVER['FOO'] = 'bar';

        $runner = new Runner($application, 500, array_filter([$middleware]));

        $assertMiddlewareInvoked = null === $expectException && $middleware && method_exists($middleware, 'isInvoked');
        if ($assertMiddlewareInvoked) {
            $this->assertFalse($middleware::isInvoked());
        }

        $this->assertSame(0, $runner->run());

        if ($assertMiddlewareInvoked) {
            $this->assertTrue($middleware::isInvoked());
        }
    }
}
