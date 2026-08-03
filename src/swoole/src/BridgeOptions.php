<?php

namespace Runtime\Swoole;

/**
 * Tunables for SymfonyHttpBridge::reflectSymfonyResponse(), grouped into one object so adding a
 * new knob later never means adding another positional parameter to the bridge's signature.
 *
 * @author Piotr Kugla <piku235@gmail.com>
 */
class BridgeOptions
{
    private const DEFAULT_STREAMED_RESPONSE_CHUNK_SIZE = 4096;

    /** @var int */
    private $streamedResponseChunkSize;

    public function __construct(int $streamedResponseChunkSize = self::DEFAULT_STREAMED_RESPONSE_CHUNK_SIZE)
    {
        $this->streamedResponseChunkSize = $streamedResponseChunkSize;
    }

    /**
     * Builds options from a ServerFactory-style options array — the same shape ServerFactory
     * itself accepts, so a single $options array can configure both.
     */
    public static function fromArray(array $options): self
    {
        return new self(
            (int) ($options['streamed_response_chunk_size'] ?? self::DEFAULT_STREAMED_RESPONSE_CHUNK_SIZE)
        );
    }

    /**
     * Size, in bytes, at which a StreamedResponse's output buffer is flushed to the Swoole
     * response. The default trades latency for fewer write() syscalls, which is fine for
     * responses that fill it quickly (e.g. files) but starves a Server-Sent Events response,
     * whose chunks are small and spaced out in time: nothing reaches the client until this many
     * bytes accumulate or the response ends, which for a long-lived SSE connection reads as the
     * connection hanging and can trip a reverse proxy's origin timeout. Use 1 for SSE (or any
     * response where each write must reach the client immediately).
     */
    public function getStreamedResponseChunkSize(): int
    {
        return $this->streamedResponseChunkSize;
    }
}
