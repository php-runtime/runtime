<?php

namespace Runtime\Swoole\Tests\E2E;

use PHPUnit\Framework\TestCase;

use function Swoole\Coroutine\Http\get;
use function Swoole\Coroutine\run;

class StaticFileHandlerTest extends TestCase
{
    public function testSwooleServerHandlesStaticFiles(): void
    {
        run(static function (): void {
            self::assertSame("Static file\n", get('http://localhost:8001/file.txt')->getBody());
        });
    }
}
