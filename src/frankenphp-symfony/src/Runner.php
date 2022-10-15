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
    private HttpKernelInterface $kernel;

    public function __construct(HttpKernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function run(): int
    {
        $server = array_filter($_SERVER, static fn (string $key) => !str_starts_with($key, 'HTTP_'), ARRAY_FILTER_USE_KEY);
        do {
            $ret = \frankenphp_handle_request(function () use ($server, &$sfRequest, &$sfResponse): void {
                // Merge the environment variables coming from DotEnv with the ones tight to the current request
                $_SERVER += $server;

                $sfRequest = Request::createFromGlobals();
                $sfResponse = $this->kernel->handle($sfRequest);

                $sfResponse->send();
            });

            if ($this->kernel instanceof TerminableInterface && $sfRequest && $sfResponse) {
                $this->kernel->terminate($sfRequest, $sfResponse);
            }
        } while ($ret);

        return 0;
    }
}
