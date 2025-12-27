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
        } while ($ret && (-1 === $this->loopMax || $loops < $this->loopMax) && $this->hasAvailableMemory($loops));

        return 0;
    }

    private function hasAvailableMemory(int $loops): bool
    {
        if (-1 === $this->minMemoryMb || 1 === $loops) { // Always allow the first cycle
            return true;
        }

        $memoryLimit = \ini_get('memory_limit');
        if (-1 === (int) $memoryLimit || '' === $memoryLimit) {
            return true;
        }
        $memoryLimit = \trim($memoryLimit);
        if (strlen($memoryLimit) < 2) {
            return true; // Unexpected value, consider enough mem available
        }
        $last = \strtolower($memoryLimit[\strlen($memoryLimit) - 1]);
        if (!\in_array($last, ['g', 'm', 'k'])) {
            return true; // Unexpected value, consider enough mem available
        }

        $value = (int) $memoryLimit; // (int) stops at first non-numeric char
        $memoryLimitMb = match ($last) {
            'g' => $value * 1024,
            'k' => (int) ($value / 1024),
            default => $value,
        };
        $usedMemoryMb = \memory_get_usage(true) / 1024 / 1024;
        $availableMemoryMb = $memoryLimitMb - $usedMemoryMb;

        return $availableMemoryMb >= $this->minMemoryMb;
    }
}
