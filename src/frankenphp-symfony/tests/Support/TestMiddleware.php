<?php

declare(strict_types=1);

namespace Runtime\FrankenPhpSymfony\Tests\Support;

use Runtime\FrankenPhpSymfony\Middleware\MiddlewareInterface;

class TestMiddleware implements MiddlewareInterface
{
    public function __construct()
    {
        self::$invoked = false;
    }

    private static bool $invoked = false;

    public function wrap(callable $handler, array $server): void
    {
        self::$invoked = true;
        $handler();
    }

    public static function isInvoked(): bool
    {
        return self::$invoked;
    }
}
