<?php

namespace Runtime\RoadRunnerSymfonyNyholm;

use Nyholm\Psr7;
use Spiral\RoadRunner;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
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

    public function __construct(KernelInterface $kernel, ?HttpFoundationFactoryInterface $httpFoundationFactory = null, ?HttpMessageFactoryInterface $httpMessageFactory = null)
    {
        $this->kernel = $kernel;
        $this->psrFactory = new Psr7\Factory\Psr17Factory();
        $this->httpFoundationFactory = $httpFoundationFactory ?? new HttpFoundationFactory();
        $this->httpMessageFactory = $httpMessageFactory ?? new PsrHttpFactory($this->psrFactory, $this->psrFactory, $this->psrFactory, $this->psrFactory);

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

                $sessionName = $this->sessionOptions['name'] ?? \session_name();
                $requestSessionId = $sfRequest->cookies->get($sessionName, '');

                // TODO invalid session id should be expired: see F at https://github.com/php-runtime/runtime/issues/46
                \session_id($requestSessionId);

                /** @var Response $sfResponse */
                $sfResponse = $this->kernel->handle($sfRequest);

                if ($sfRequest->hasSession()) {
                    $sessionId = \session_id();
                    // we can not use $session->isStarted() here as this state is not longer available at this time
                    // TODO session cookie should only be set when persisted by symfony: see E at https://github.com/php-runtime/runtime/issues/46
                    if ($sessionId && $sessionId !== $requestSessionId) {
                        $expires = 0;
                        $lifetime = $this->sessionOptions['cookie_lifetime'] ?? null;
                        if ($lifetime) {
                            $expires = time() + $lifetime;
                        }

                        $sfResponse->headers->setCookie(
                            Cookie::create(
                                $sessionName,
                                $sessionId,
                                $expires,
                                $this->sessionOptions['cookie_path'] ?? '/',
                                $this->sessionOptions['cookie_domain'] ?? null,
                                $this->sessionOptions['cookie_secure'] ?? null,
                                $this->sessionOptions['cookie_httponly'] ?? true,
                                false,
                                $this->sessionOptions['cookie_samesite'] ?? Cookie::SAMESITE_LAX,
                            )
                        );
                    }
                }

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
}
