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

    public static function reflectSymfonyResponse(SymfonyResponse $sfResponse, Response $response, ?BridgeOptions $options = null): void
    {
        $options = $options ?? new BridgeOptions();

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
                    // At a small chunk size (e.g. 1, for SSE) every echo already flushes on its
                    // own, so the buffer is typically empty by the time ob_end_clean() makes its
                    // own final call to this handler. Swoole\Http\Response::write() itself checks
                    // length === 0 and rejects it with a warning ("the data sent must not be
                    // empty"), so this mirrors that same check to skip the call instead.
                    if (strlen($buffer) > 0) {
                        $response->write($buffer);
                    }

                    return '';
                }, $options->getStreamedResponseChunkSize());
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
