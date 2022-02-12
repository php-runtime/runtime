<?php

namespace Runtime\PsrNyholm;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Runtime\GenericRuntime;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A runtime that supports PSR-7, PSR-15 and PSR-17.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Runtime extends GenericRuntime
{
    /**
     * @var ServerRequestCreator|null
     */
    private $requestCreator;

    public function getRunner(?object $application): RunnerInterface
    {
        if ($application instanceof RequestHandlerInterface) {
            return Emitter::createForRequestHandler($application, $this->createRequest());
        }

        if ($application instanceof ResponseInterface) {
            return Emitter::createForResponse($application);
        }

        return parent::getRunner($application);
    }

    protected function getArgument(\ReflectionParameter $parameter, ?string $type): mixed
    {
        if (ServerRequestInterface::class === $type) {
            return $this->createRequest();
        }

        return parent::getArgument($parameter, $type);
    }

    protected static function register(GenericRuntime $runtime): GenericRuntime
    {
        $self = new self($runtime->options + ['runtimes' => []]);
        $self->options['runtimes'] += [
            ServerRequestInterface::class => $self,
            ResponseInterface::class => $self,
            RequestHandlerInterface::class => $self,
        ];
        $runtime->options = $self->options;

        return $self;
    }

    /**
     * @return ServerRequestInterface
     */
    private function createRequest()
    {
        if (null === $this->requestCreator) {
            $psr17Factory = new Psr17Factory();
            $this->requestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        }

        return $this->requestCreator->fromGlobals();
    }
}
