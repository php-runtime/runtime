<?php

namespace Runtime\PsrLaminas\Tests;

use PHPUnit\Framework\TestCase;
use Runtime\PsrLaminas\Runtime;

class AutoDiscoverTest extends TestCase
{
    public function testAutoDiscoverClasses()
    {
        $classes = [
            'Symfony\Runtime\Psr\Http\Message\ResponseInterfaceRuntime',
            'Symfony\Runtime\Psr\Http\Message\ServerRequestInterfaceRuntime',
            'Symfony\Runtime\Psr\Http\Server\RequestHandlerInterfaceRuntime',
        ];

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
            $this->assertTrue(is_subclass_of($class, Runtime::class));
        }
    }
}
