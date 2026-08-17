<?php

declare(strict_types=1);

namespace Runtime\Swoole\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Runtime\Swoole\BridgeOptions;

class BridgeOptionsTest extends TestCase
{
    public function testDefaultStreamedResponseChunkSize(): void
    {
        $options = new BridgeOptions();

        self::assertSame(4096, $options->getStreamedResponseChunkSize());
    }

    public function testExplicitStreamedResponseChunkSize(): void
    {
        $options = new BridgeOptions(1);

        self::assertSame(1, $options->getStreamedResponseChunkSize());
    }

    public function testFromArrayWithGivenChunkSize(): void
    {
        $options = BridgeOptions::fromArray(['streamed_response_chunk_size' => 1]);

        self::assertSame(1, $options->getStreamedResponseChunkSize());
    }

    public function testFromArrayFallsBackToDefaultChunkSize(): void
    {
        $options = BridgeOptions::fromArray([]);

        self::assertSame(4096, $options->getStreamedResponseChunkSize());
    }
}
