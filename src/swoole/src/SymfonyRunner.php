<?php

namespace Runtime\Swoole;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A runner for Symfony.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyRunner implements RunnerInterface
{
    /** @var ServerFactory */
    private $serverFactory;
    /** @var HttpKernelInterface */
    private $application;
    /** @var BridgeOptions */
    private $bridgeOptions;

    public function __construct(ServerFactory $serverFactory, HttpKernelInterface $application)
    {
        $this->serverFactory = $serverFactory;
        $this->application = $application;
        $this->bridgeOptions = BridgeOptions::fromArray($serverFactory->getOptions());
    }

    public function run(): int
    {
        $this->serverFactory->createServer([$this, 'handle'])->start();

        return 0;
    }

    public function handle(Request $request, Response $response): void
    {
        $sfRequest = SymfonyHttpBridge::convertSwooleRequest($request);

        self::applyTrustedProxies($sfRequest);

        $sfResponse = $this->application->handle($sfRequest);
        SymfonyHttpBridge::reflectSymfonyResponse($sfResponse, $response, $this->bridgeOptions);

        if ($this->application instanceof TerminableInterface) {
            $this->application->terminate($sfRequest, $sfResponse);
        }
    }

    /**
     * Symfony's Kernel resolves trusted_proxies (incl. the 'REMOTE_ADDR' sentinel, which reads
     * $_SERVER) once, at boot() — the first request this long-lived worker ever handles. Every
     * request after that keeps whatever REMOTE_ADDR happened to be at that one moment, so proxy
     * trust silently breaks for the rest of the worker's life. Re-resolves it here instead, per
     * request, from TRUSTED_PROXIES/TRUSTED_HEADERS env vars. No-op if TRUSTED_PROXIES is unset.
     */
    private static function applyTrustedProxies(SymfonyRequest $sfRequest): void
    {
        $proxiesEnv = $sfRequest->server->get('TRUSTED_PROXIES') ?? $_ENV['TRUSTED_PROXIES'] ?? $_SERVER['TRUSTED_PROXIES'] ?? null;
        if (null === $proxiesEnv || '' === $proxiesEnv) {
            return;
        }

        $proxies = array_map('trim', explode(',', $proxiesEnv));

        if (false !== $i = array_search('REMOTE_ADDR', $proxies, true)) {
            $remoteAddr = $sfRequest->server->get('REMOTE_ADDR');
            if (null !== $remoteAddr && '' !== $remoteAddr) {
                $proxies[$i] = $remoteAddr;
            } else {
                unset($proxies[$i]);
                $proxies = array_values($proxies);
            }
        }

        $headersEnv = $sfRequest->server->get('TRUSTED_HEADERS') ?? $_ENV['TRUSTED_HEADERS'] ?? $_SERVER['TRUSTED_HEADERS']
            ?? 'x-forwarded-for,x-forwarded-host,x-forwarded-proto,x-forwarded-port';
        $trustedHeaderSet = 0;
        foreach (array_map('trim', explode(',', $headersEnv)) as $header) {
            $const = SymfonyRequest::class.'::HEADER_'.strtr(strtoupper($header), '-', '_');
            if (\defined($const)) {
                $trustedHeaderSet |= \constant($const);
            }
        }

        SymfonyRequest::setTrustedProxies($proxies, $trustedHeaderSet);
    }
}
