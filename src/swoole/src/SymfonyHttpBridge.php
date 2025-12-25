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

        return $sfRequest;
    }

    public static function reflectSymfonyResponse(SymfonyResponse $sfResponse, Response $response): void
    {
        foreach ($sfResponse->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            foreach ((array) $values as $value) {
                $response->header((string) $name, $value);
            }
        }

        foreach ($sfResponse->headers->getCookies() as $cookie) {
            $response->cookie(
                $cookie->getName(),
                $cookie->getValue() ?? '',
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain() ?? '',
                $cookie->isSecure(),
                $cookie->isHttpOnly(),
                $cookie->getSameSite() ?? ''
            );
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
}
