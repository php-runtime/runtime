<?php

declare(strict_types=1);

namespace Runtime\FrankenPhpSymfony\Middleware;

interface MiddlewareInterface
{
    public function wrap(callable $handler, array $server): void;
}
