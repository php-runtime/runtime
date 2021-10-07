<?php

namespace Runtime\Swoole;

use Swoole\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Bridge between Symfony and Swoole Http API.
 *
 * @author Piotr Kugla <piku235@gmail.com>
 *
 * @internal
 */
final class SymfonyHttpBridge
{
    public static function reflectSymfonyResponse(SymfonyResponse $sfResponse, Response $response): void
    {
        foreach ($sfResponse->headers->all() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        $response->status($sfResponse->getStatusCode());
        $response->end($sfResponse->getContent());
    }
}
