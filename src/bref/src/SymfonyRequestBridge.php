<?php

namespace Runtime\Bref;

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Riverline\MultiPartParser\StreamedPart;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bridges Symfony requests and responses with API Gateway or ALB event/response
 * formats.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @internal
 */
class SymfonyRequestBridge
{
    public static function convertRequest(HttpRequestEvent $event, Context $context): Request
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);

        // CGI Version 1.1 - Section 4.1
        $server = array_filter([
            'AUTH_TYPE' => $event->getHeaders()['auth-type'] ?? null, // 4.1.1
            'CONTENT_LENGTH' => $event->getHeaders()['content-length'][0] ?? null, // 4.1.2
            'CONTENT_TYPE' => $event->getContentType(), // 4.1.3
            'QUERY_STRING' => $event->getQueryString(), // 4.1.7
            'REQUEST_METHOD' => $event->getMethod(), // 4.1.12
            'SERVER_PORT' => $event->getServerPort(), // 4.1.16
            'SERVER_PROTOCOL' => 'HTTP/'.$event->getProtocolVersion(), // 4.1.16
            'DOCUMENT_ROOT' => getcwd(),
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'REQUEST_URI' => $event->getUri(),
            'REMOTE_ADDR' => '127.0.0.1',
            'LAMBDA_INVOCATION_CONTEXT' => json_encode($context),
            'LAMBDA_REQUEST_CONTEXT' => json_encode($event->getRequestContext()),
        ], fn ($value) => null !== $value);

        foreach ($event->getHeaders() as $name => $values) {
            $server['HTTP_'.strtoupper($name)] = $values[0];
        }

        [$files, $parsedBody, $bodyString] = self::parseBodyAndUploadedFiles($event);

        return new Request(
            $event->getQueryParameters(),
            $parsedBody ?? [],
            [], // Attributes
            $event->getCookies(),
            $files,
            $server,
            $bodyString
        );
    }

    public static function convertResponse(Response $response): HttpResponse
    {
        return new HttpResponse($response->getContent(), $response->headers->all(), $response->getStatusCode());
    }

    private static function parseBodyAndUploadedFiles(HttpRequestEvent $event): array
    {
        $bodyString = $event->getBody();
        $files = [];
        $parsedBody = null;
        $contentType = $event->getContentType();
        if (null !== $contentType && 'POST' === $event->getMethod()) {
            if ('application/x-www-form-urlencoded' === $contentType) {
                parse_str($bodyString, $parsedBody);
            } else {
                $stream = fopen('php://temp', 'rw');
                fwrite($stream, "Content-type: $contentType\r\n\r\n".$bodyString);
                rewind($stream);
                $document = new StreamedPart($stream);
                if ($document->isMultiPart()) {
                    $bodyString = '';
                    $parsedBody = [];
                    foreach ($document->getParts() as $part) {
                        if ($part->isFile()) {
                            $tmpPath = tempnam(sys_get_temp_dir(), 'bref_upload_');
                            if (false === $tmpPath) {
                                throw new \RuntimeException('Unable to create a temporary directory');
                            }
                            file_put_contents($tmpPath, $part->getBody());
                            if (0 !== filesize($tmpPath) && '' !== $part->getFileName()) {
                                $file = new UploadedFile($tmpPath, $part->getFileName(), $part->getMimeType(), UPLOAD_ERR_OK, true);
                            } else {
                                $file = null;
                            }

                            self::parseKeyAndInsertValueInArray($files, $part->getName(), $file);
                        } else {
                            self::parseKeyAndInsertValueInArray($parsedBody, $part->getName(), $part->getBody());
                        }
                    }
                }
            }
        }

        return [$files, $parsedBody, $bodyString];
    }

    /**
     * Parse a string key like "files[id_cards][jpg][]" and do $array['files']['id_cards']['jpg'][] = $value.
     *
     * @param mixed $value
     */
    private static function parseKeyAndInsertValueInArray(array &$array, string $key, $value): void
    {
        if (false === strpos($key, '[')) {
            $array[$key] = $value;

            return;
        }

        $parts = explode('[', $key); // files[id_cards][jpg][] => [ 'files',  'id_cards]', 'jpg]', ']' ]
        $pointer = &$array;

        foreach ($parts as $k => $part) {
            if (0 === $k) {
                $pointer = &$pointer[$part];

                continue;
            }

            // Skip two special cases:
            // [[ in the key produces empty string
            // [test : starts with [ but does not end with ]
            if ('' === $part || ']' !== substr($part, -1)) {
                // Malformed key, we use it "as is"
                $array[$key] = $value;

                return;
            }

            $part = substr($part, 0, -1); // The last char is a ] => remove it to have the real key

            if ('' === $part) { // [] case
                $pointer = &$pointer[];
            } else {
                $pointer = &$pointer[$part];
            }
        }

        $pointer = $value;
    }
}
