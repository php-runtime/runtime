<?php

namespace Runtime\RoadRunnerSymfonyNyholm;

use Nyholm\Psr7;
use Spiral\RoadRunner;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
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

    public function __construct(HttpKernelInterface $kernel, ?HttpFoundationFactoryInterface $httpFoundationFactory = null, ?HttpMessageFactoryInterface $httpMessageFactory = null)
    {
        $this->kernel = $kernel;
        $this->psrFactory = new Psr7\Factory\Psr17Factory();
        $this->httpFoundationFactory = $httpFoundationFactory ?? new HttpFoundationFactory();
        $this->httpMessageFactory = $httpMessageFactory ?? new PsrHttpFactory($this->psrFactory, $this->psrFactory, $this->psrFactory, $this->psrFactory);
    }

    public function run(): int
    {
        $worker = RoadRunner\Worker::create();
        $worker = new RoadRunner\Http\PSR7Worker($worker, $this->psrFactory, $this->psrFactory, $this->psrFactory);

        while ($request = $worker->waitRequest()) {
            try {
                $sfRequest = $this->httpFoundationFactory->createRequest($request);
                $sfResponse = $this->kernel->handle($sfRequest);
                $worker->respond($this->httpMessageFactory->createResponse($sfResponse));

                if ($this->kernel instanceof TerminableInterface) {
                    $this->kernel->terminate($sfRequest, $sfResponse);
                }
            } catch (\Throwable $e) {
                $worker->getWorker()->error((string) $e);
            }
        }

        return 0;
    }
}
