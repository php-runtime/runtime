<?php

declare(strict_types=1);

namespace Runtime\FrankenPhpSymfony;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A response runner for FrankenPHP.
 *
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class ResponseRunner implements RunnerInterface
{
    public function __construct(
        private Response $response,
        private int $loopMax,
    ) {
    }

    public function run(): int
    {
        // Prevent worker script termination when a client connection is interrupted
        ignore_user_abort(true);

        $xdebugConnectToClient = function_exists('xdebug_connect_to_client');

        $server = array_filter($_SERVER, static fn (string $key) => !str_starts_with($key, 'HTTP_'), ARRAY_FILTER_USE_KEY);
        $server['APP_RUNTIME_MODE'] = 'web=1&worker=1';

        $handler = function () use ($server, $xdebugConnectToClient): void {
            // Connect to the Xdebug client if it's available
            if ($xdebugConnectToClient) {
                xdebug_connect_to_client();
            }

            // Merge the environment variables coming from DotEnv with the ones tied to the current request
            $_SERVER += $server;

            $this->response->send();
        };

        $loops = 0;
        do {
            $ret = \frankenphp_handle_request($handler);

            gc_collect_cycles();
        } while ($ret && (-1 === $this->loopMax || ++$loops < $this->loopMax));

        return 0;
    }
}
