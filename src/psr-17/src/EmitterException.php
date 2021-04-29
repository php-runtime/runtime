<?php

/**
 * Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC.
 */

declare(strict_types=1);

namespace Runtime\Psr17;

class EmitterException extends \RuntimeException
{
    public static function forHeadersSent(): self
    {
        return new self('Unable to emit response; headers already sent');
    }

    public static function forOutputSent(): self
    {
        return new self('Output has been emitted previously; cannot emit response');
    }
}
