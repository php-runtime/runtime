<?php

namespace Runtime\Swoole;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
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
    public static function convertSwooleRequest(Request $request): SymfonyRequest
    {
        $sfRequest = new SymfonyRequest(
            $request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            array_change_key_case($request->server ?? [], CASE_UPPER),
            $request->rawContent()
        );
        $sfRequest->headers = new HeaderBag($request->header ?? []);

        return $sfRequest;
    }

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
