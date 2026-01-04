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
    public function __construct(
        private HttpKernelInterface $kernel,
        private int $loopMax,
        private int $minMemoryMb = -1,
    ) {
    }

    public function run(): int
    {
        // Prevent worker script termination when a client connection is interrupted
        ignore_user_abort(true);

        $xdebugConnectToClient = function_exists('xdebug_connect_to_client');

        $server = array_filter($_SERVER, static fn (string $key) => !str_starts_with($key, 'HTTP_'), ARRAY_FILTER_USE_KEY);
        $server['APP_RUNTIME_MODE'] = 'web=1&worker=1';

        $handler = function () use ($server, &$sfRequest, &$sfResponse, $xdebugConnectToClient): void {
            // Connect to the Xdebug client if it's available
            if ($xdebugConnectToClient) {
                xdebug_connect_to_client();
            }

            // Merge the environment variables coming from DotEnv with the ones tied to the current request
            $_SERVER += $server;

            $sfRequest = Request::createFromGlobals();
            $sfResponse = $this->kernel->handle($sfRequest);

            $sfResponse->send();
        };

        $loops = 0;
        do {
            $ret = \frankenphp_handle_request($handler);

            if ($this->kernel instanceof TerminableInterface && $sfRequest && $sfResponse) {
                $this->kernel->terminate($sfRequest, $sfResponse);
            }

            gc_collect_cycles();
            ++$loops;
        } while ($ret && (-1 === $this->loopMax || $loops < $this->loopMax) && $this->hasAvailableMemory());

        return 0;
    }

    private function hasAvailableMemory(): bool
    {
        if (-1 === $this->minMemoryMb) {
            return true;
        }

        $memoryLimit = \trim((string) \ini_get('memory_limit'));
        if (-1 === (int) $memoryLimit || '' === $memoryLimit) {
            return true;
        }

        $last = \strtolower($memoryLimit[\strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit; // (int) stops at first non-numeric char
        $memoryLimitMb = match ($last) {
            'g' => $value * 1024,
            'm' => $value,
            'k' => $value / 1024,
            default => $value / 1024 / 1024, // bytes
        };
        $usedMemoryMb = \memory_get_usage(true) / 1024 / 1024;
        $availableMemoryMb = $memoryLimitMb - $usedMemoryMb;

        return $availableMemoryMb >= $this->minMemoryMb;
    }
}
