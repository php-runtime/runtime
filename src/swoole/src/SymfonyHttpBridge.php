<?php

namespace Runtime\Swoole;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        self::prepareRequestUriWithQueryString($sfRequest);

        return $sfRequest;
    }

    public static function reflectSymfonyResponse(SymfonyResponse $sfResponse, Response $response): void
    {
        foreach ($sfResponse->headers->all() as $name => $values) {
            foreach ((array) $values as $value) {
                $response->header((string) $name, $value);
            }
        }

        $response->status($sfResponse->getStatusCode());

        switch (true) {
            case $sfResponse instanceof BinaryFileResponse && $sfResponse->headers->has('Content-Range'):
            case $sfResponse instanceof StreamedResponse:
                ob_start(function ($buffer) use ($response) {
                    $response->write($buffer);

                    return '';
                }, 4096);
                $sfResponse->sendContent();
                ob_end_clean();
                $response->end();
                break;
            case $sfResponse instanceof BinaryFileResponse:
                $response->sendfile($sfResponse->getFile()->getPathname());
                break;
            default:
                $response->end($sfResponse->getContent());
        }
    }

    /**
     * REQUEST_URI returns the uri path only, should append with the query string if exists.
     */
    private static function prepareRequestUriWithQueryString(SymfonyRequest $sfRequest): void
    {
        if (!str_contains($sfRequest->server->get('REQUEST_URI', ''), '?')
            && $sfRequest->server->has('QUERY_STRING')
            && strlen($sfRequest->server->get('QUERY_STRING')) > 0
        ) {
            $sfRequest->server->set(
                'REQUEST_URI',
                sprintf('%s?%s', $sfRequest->server->get('REQUEST_URI'), $sfRequest->server->get('QUERY_STRING')),
            );
        }
    }
}
