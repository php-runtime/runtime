<?php

namespace Runtime\Bref\Tests;

use PHPUnit\Framework\TestCase;
use Runtime\Bref\Timeout\LambdaTimeoutException;
use Runtime\Bref\Timeout\Timeout;

class TimeoutTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!function_exists('pcntl_async_signals')) {
            self::markTestSkipped('PCNTL extension is not enabled.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        unset($_SERVER['LAMBDA_INVOCATION_CONTEXT']);
    }

    protected function tearDown(): void
    {
        Timeout::reset();
        parent::tearDown();
    }

    public function testEnable()
    {
        Timeout::enable(3000);
        $timeout = pcntl_alarm(0);
        // 1 second (2 seconds shorter than the 3s remaining time)
        $this->assertSame(1, $timeout);
    }

    public function testTimeoutsAreInterruptedInTime()
    {
        $start = microtime(true);
        Timeout::enable(3000);
        try {
            sleep(4);
            $this->fail('We expect a LambdaTimeout before we reach this line');
        } catch (LambdaTimeoutException $e) {
            $time = 1000 * (microtime(true) - $start);
            $this->assertEqualsWithDelta(1000, $time, 200, 'We must wait about 1 second');
            Timeout::reset();
        } catch (\Throwable $e) {
            $this->fail('It must throw a LambdaTimeout.');
        }
    }
}
