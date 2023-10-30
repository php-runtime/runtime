<?php

declare(strict_types=1);

namespace Runtime\FrankenPhpSymfony;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A runner for FrankenPHP.
 *
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class Runner implements RunnerInterface
{
    public function __construct(private HttpKernelInterface $kernel)
    {
    }

    public function run(): int
    {
        $kernel = $this->kernel;
        $server = array_filter($_SERVER, static fn (string $key) => !str_starts_with($key, 'HTTP_'), ARRAY_FILTER_USE_KEY);
        $server['APP_RUNTIME_MODE'] = 'web=1&worker=1';

        $handler = static function () use ($kernel, $server, &$sfRequest, &$sfResponse): void {
            // Merge the environment variables coming from DotEnv with the ones tied to the current request
            $_SERVER += $server;

            $sfRequest = Request::createFromGlobals();
            $sfResponse = $kernel->handle($sfRequest);

            $sfResponse->send();
        };

        do {
            $ret = \frankenphp_handle_request($handler);

            if ($kernel instanceof TerminableInterface && $sfRequest && $sfResponse) {
                $kernel->terminate($sfRequest, $sfResponse);
            }

            gc_collect_cycles();
        } while ($ret);

        return 0;
    }
}
