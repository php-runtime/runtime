<?php

declare(strict_types=1);

namespace Runtime\FrankenPhpSymfony\Exception;

class InvalidMiddlewareException extends \Exception
{
    public function __construct(public string $className, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            "The middleware class '$className' is invalid.",
            $code,
            $previous
        );
    }
}
