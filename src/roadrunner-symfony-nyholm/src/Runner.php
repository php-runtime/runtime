<?php

namespace Runtime\RoadRunnerSymfonyNyholm;

use Nyholm\Psr7;
use Spiral\RoadRunner;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Runner implements RunnerInterface
{
    private $kernel;
    private $httpFoundationFactory;
    private $httpMessageFactory;
    private $psrFactory;

    /**
     * @var array<string, mixed>
     */
    private $sessionOptions;

    /**
     * @param HttpKernelInterface|KernelInterface $kernel
     */
    public function __construct($kernel, ?HttpFoundationFactoryInterface $httpFoundationFactory = null, ?HttpMessageFactoryInterface $httpMessageFactory = null)
    {
        $this->kernel = $kernel;
        $this->psrFactory = new Psr7\Factory\Psr17Factory();
        $this->httpFoundationFactory = $httpFoundationFactory ?? new HttpFoundationFactory();
        $this->httpMessageFactory = $httpMessageFactory ?? new PsrHttpFactory($this->psrFactory, $this->psrFactory, $this->psrFactory, $this->psrFactory);

        if ($kernel instanceof HttpCache) {
            $kernel = $kernel->getKernel();
        }

        if (!$kernel instanceof KernelInterface) {
            throw new \InvalidArgumentException(sprintf('Expected argument of type "%s" or "%s", "%s" given.', KernelInterface::class, HttpCache::class, get_class($kernel)));
        }

        $kernel->boot();
        $container = $kernel->getContainer();
        $this->sessionOptions = $container->getParameter('session.storage.options');
        $kernel->shutdown();
    }

    public function run(): int
    {
        $worker = RoadRunner\Worker::create();
        $worker = new RoadRunner\Http\PSR7Worker($worker, $this->psrFactory, $this->psrFactory, $this->psrFactory);

        while ($request = $worker->waitRequest()) {
            try {
                $sfRequest = $this->httpFoundationFactory->createRequest($request);
                $sfResponse = $this->handle($sfRequest);
                $worker->respond($this->httpMessageFactory->createResponse($sfResponse));

                if ($this->kernel instanceof TerminableInterface) {
                    $this->kernel->terminate($sfRequest, $sfResponse);
                }
            } catch (\Throwable $e) {
                $worker->getWorker()->error((string) $e);
            } finally {
                if (PHP_SESSION_ACTIVE === session_status()) {
                    session_abort();
                }

                // reset all session variables to initialize state
                $_SESSION = [];
                session_id(''); // in this case session_start() will generate us a new session_id()
            }
        }

        return 0;
    }

    private function handle(SymfonyRequest $request): SymfonyResponse
    {
        $sessionName = $this->sessionOptions['name'] ?? \session_name();
        /** @var string $requestSessionId */
        $requestSessionId = $request->cookies->get($sessionName, '');

        // TODO invalid session id should be expired: see F at https://github.com/php-runtime/runtime/issues/46
        \session_id($requestSessionId);

        $response = $this->kernel->handle($request);

        if ($request->hasSession()) {
            $sessionId = \session_id();
            // we can not use $session->isStarted() here as this state is not longer available at this time
            // TODO session cookie should only be set when persisted by symfony: see E at https://github.com/php-runtime/runtime/issues/46
            if ($sessionId && $sessionId !== $requestSessionId) {
                $expires = 0;
                $lifetime = $this->sessionOptions['cookie_lifetime'] ?? null;
                if ($lifetime) {
                    $expires = time() + $lifetime;
                }

                $response->headers->setCookie(
                    Cookie::create(
                        $sessionName,
                        $sessionId,
                        $expires,
                        $this->sessionOptions['cookie_path'] ?? '/',
                        $this->sessionOptions['cookie_domain'] ?? null,
                        $this->sessionOptions['cookie_secure'] ?? null,
                        $this->sessionOptions['cookie_httponly'] ?? true,
                        false,
                        $this->sessionOptions['cookie_samesite'] ?? Cookie::SAMESITE_LAX
                    )
                );
            }
        }

        return $response;
    }
}
