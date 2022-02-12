<?php

namespace Runtime\GoogleCloud;

use Google\CloudFunctions\CloudEvent;
use Google\CloudFunctions\LegacyEventMapper;
use Symfony\Component\Runtime\GenericRuntime;

class Runtime extends GenericRuntime
{
    private const TYPE_LEGACY = 1;
    private const TYPE_BINARY = 2;
    private const TYPE_STRUCTURED = 3;
    private const FUNCTION_STATUS_HEADER = 'X-Google-Status';

    // These are CloudEvent context attribute names that map to binary mode
    // HTTP headers when prefixed with 'ce-'. 'datacontenttype' is notably absent
    // from this list because the header 'ce-datacontenttype' is not permitted;
    // that data comes from the 'Content-Type' header instead. For more info see
    // https://github.com/cloudevents/spec/blob/v1.0.1/http-protocol-binding.md#311-http-content-type
    private static $binaryModeHeaderAttrs = [
        'id',
        'source',
        'specversion',
        'type',
        'dataschema',
        'subject',
        'time',
    ];

    /**
     * @return mixed
     */
    protected function getArgument(\ReflectionParameter $parameter, ?string $type)
    {
        if (CloudEvent::class === $type) {
            return $this->createCloudEvent();
        }

        return parent::getArgument($parameter, $type);
    }

    protected static function register(GenericRuntime $runtime): GenericRuntime
    {
        $self = new self($runtime->options + ['runtimes' => []]);
        $self->options['runtimes'] += [
            CloudEvent::class => $self,
        ];
        $runtime->options = $self->options;

        return $self;
    }

    protected function createCloudEvent(): ?CloudEvent
    {
        $body = $this->getBody();
        $headers = $this->getHeaders();
        $eventType = $this->getEventType($headers);

        // We expect JSON if the content-type ends in "json" or if the event
        // type is legacy or structured Cloud Event.
        $shouldValidateJson = in_array($eventType, [self::TYPE_LEGACY, self::TYPE_STRUCTURED])
            || (isset($headers['content-type']) && 'json' === substr($headers['content-type'], -4));

        if (!$shouldValidateJson) {
            $data = $body;
        } else {
            // Validate JSON
            $data = \json_decode($body, true);
            if (JSON_ERROR_NONE != json_last_error()) {
                $message = sprintf('Could not parse CloudEvent: %s', '' !== $body ? json_last_error_msg() : 'Missing cloudevent payload');
                $this->sendHttpResponseAndExit(400, $message, [self::FUNCTION_STATUS_HEADER => 'crash']);

                return null;
            }
        }

        switch ($eventType) {
            case self::TYPE_LEGACY:
                return (new LegacyEventMapper())->fromJsonData($data);

            case self::TYPE_STRUCTURED:
                return CloudEvent::fromArray($data);

            case self::TYPE_BINARY:
                return $this->fromBinaryRequest($headers, $data);

            default:
                $message = 'Could not create CloudEvent. Invalid event type';
                $this->sendHttpResponseAndExit(400, $message, [self::FUNCTION_STATUS_HEADER => 'crash']);

                return null;
        }
    }

    protected function sendHttpResponseAndExit(int $status, string $body, array $headers)
    {
        error_log($body);
        header('HTTP/1.1 '.$status);
        foreach ($headers as $name => $value) {
            header($name.': '.$value);
        }
        echo $body;

        exit(0);
    }

    protected function getHeaders(): array
    {
        $rawHeaders = \function_exists('getallheaders') ? getallheaders() : $this->getHeadersFromServer($_SERVER);
        $headers = [];
        foreach ($rawHeaders as $name => $value) {
            $headers[strtolower($name)] = $value;
        }

        return $headers;
    }

    protected function getBody(): string
    {
        $body = \fopen('php://input', 'r') ?: null;
        if (null === $body) {
            $message = 'Could not create CloudEvent from request with no body';
            $this->sendHttpResponseAndExit(400, $message, [self::FUNCTION_STATUS_HEADER => 'crash']);

            return '';
        }

        return stream_get_contents($body);
    }

    /**
     * @psalm-return self::TYPE_*
     */
    private function getEventType(array $headers): int
    {
        if (isset($headers['ce-type'], $headers['ce-specversion'], $headers['ce-source'], $headers['ce-id'])) {
            return self::TYPE_BINARY;
        }

        if (isset($headers['content-type']) && 'application/cloudevents+json' === $headers['content-type']) {
            return self::TYPE_STRUCTURED;
        }

        return self::TYPE_LEGACY;
    }

    /**
     * @param string|array $data
     */
    private function fromBinaryRequest(array $headers, $data): CloudEvent
    {
        $content = [];
        foreach (self::$binaryModeHeaderAttrs as $attr) {
            $ceHeader = 'ce-'.$attr;
            if (isset($headers[$ceHeader])) {
                $content[$attr] = $headers[$ceHeader];
            }
        }
        $content['data'] = $data;

        // For binary mode events the 'Content-Type' header corresponds to the
        // 'datacontenttype' attribute. There is no 'ce-datacontenttype' header.
        if (isset($headers['content-type'])) {
            $content['datacontenttype'] = $headers['content-type'];
        }

        return CloudEvent::fromArray($content);
    }

    /**
     * Implementation from Zend\Diactoros\marshalHeadersFromSapi().
     */
    private function getHeadersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            // Apache prefixes environment variables with REDIRECT_
            // if they are added by rewrite rules
            if (0 === \strpos($key, 'REDIRECT_')) {
                $key = \substr($key, 9);

                // We will not overwrite existing variables with the
                // prefixed versions, though
                if (\array_key_exists($key, $server)) {
                    continue;
                }
            }

            if ($value && 0 === \strpos($key, 'HTTP_')) {
                $name = \strtr(\strtolower(\substr($key, 5)), '_', '-');
                $headers[$name] = $value;

                continue;
            }

            if ($value && 0 === \strpos($key, 'CONTENT_')) {
                $name = 'content-'.\strtolower(\substr($key, 8));
                $headers[$name] = $value;

                continue;
            }
        }

        return $headers;
    }
}
