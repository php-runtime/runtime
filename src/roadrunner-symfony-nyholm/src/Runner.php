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
use Symfony\Component\HttpKernel\Kernel;
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

    public function __construct(Kernel $kernel, ?HttpFoundationFactoryInterface $httpFoundationFactory = null, ?HttpMessageFactoryInterface $httpMessageFactory = null)
    {
        $this->kernel = $kernel;
        $this->psrFactory = new Psr7\Factory\Psr17Factory();
        $this->httpFoundationFactory = $httpFoundationFactory ?? new HttpFoundationFactory();
        $this->httpMessageFactory = $httpMessageFactory ?? new PsrHttpFactory($this->psrFactory, $this->psrFactory, $this->psrFactory, $this->psrFactory);

        $kernel->boot();
        $container = $kernel->getContainer();
        /** @var array<string, mixed> $sessionOptions */
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
                \session_id($requestSessionId);

                /** @var Response $sfResponse */
                $sfResponse = $this->kernel->handle($sfRequest);

                $hasRequestSession = $sfRequest->hasSession();
                if ($hasRequestSession) {
                    $session = $sfRequest->getSession();

                    $sessionId = \session_id();
                    // we can not use $session->isStarted() here as this state is not longer available at this time
                    $writeSessionCookie = $sessionId
                        && $sessionId !== $requestSessionId;

                    if ($writeSessionCookie) {
                        $expires = 0;
                        $lifetime = $sessionOptions['cookie_lifetime'] ?? null;
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

                $sfResponse->headers->set('X-REQUEST-SESSION-ID', $requestSessionId);
                $sfResponse->headers->set('X-NEW-SESSION-ID', $sessionId);
                $sfResponse->headers->set('X-SESSION-STATUS', session_status() === \PHP_SESSION_ACTIVE ? 'active' : 'none');
                $sfResponse->headers->set('X-SESSION-STARTED', $session->isStarted() ? 'true' : 'false');
                $sfResponse->headers->set('X-WRITE-SESSION', $writeSessionCookie ? 'true' : 'false');
                $sfResponse->headers->set('X-STRICT-SESSION', \ini_get('session.use_strict_mode') ? 'true' : 'false');
                $sfResponse->headers->set('X-HEADERS', \json_encode(headers_list(), \JSON_PRETTY_PRINT));

                $worker->respond($this->httpMessageFactory->createResponse($sfResponse));

                if ($this->kernel instanceof TerminableInterface) {
                    $this->kernel->terminate($sfRequest, $sfResponse);
                }
            } catch (\Throwable $e) {
                $worker->getWorker()->error((string) $e);
            } finally {
                if (session_status() === PHP_SESSION_ACTIVE) {
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
