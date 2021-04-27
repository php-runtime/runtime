<?php

namespace Runtime\GoogleCloud\Tests;

use PHPUnit\Framework\TestCase;
use Runtime\GoogleCloud\Runtime;

class AutoDiscoverTest extends TestCase
{
    public function testAutoDiscoverClasses()
    {
        $classes = [
            'Symfony\Runtime\Google\CloudFunctions\CloudEventRuntime',
        ];

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
            $this->assertTrue(is_subclass_of($class, Runtime::class));
        }
    }
}
