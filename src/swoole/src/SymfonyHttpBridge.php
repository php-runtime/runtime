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
        return new SymfonyRequest(
            $request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            self::buildServer($request),
            $request->rawContent()
        );
    }

    public static function reflectSymfonyResponse(SymfonyResponse $sfResponse, Response $response): void
    {
        foreach ($sfResponse->headers->all() as $name => $values) {
            $response->header((string) $name, $values);
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
     * We must add the headers to the server otherwise they will not be available in sub-requests
     */
    private static function buildServer(Request $request): array
    {
        $serverHeaders = [];
        if (true === is_array($request->header)) {
            foreach ($request->header as $name => $value) {
                $name = strtoupper($name);

                if (
                    false === in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])
                    &&  false === str_starts_with($name, 'HTTP_')
                ) {
                    $name = sprintf('HTTP_%s', $name);
                }

                $serverHeaders[$name] = $value;
            }
        }

        return array_merge(
            array_change_key_case($request->server ?? [], CASE_UPPER),
            $serverHeaders
        );
    }
}
