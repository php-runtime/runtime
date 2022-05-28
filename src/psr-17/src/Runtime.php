<?php

namespace Runtime\Psr17;

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

    /**
     * @param array{
     *   debug?: ?bool,
     *   server_request_creator?: ?string,
     *   psr17_server_request_factory?: ?string,
     *   psr17_uri_factory?: ?string,
     *   psr17_uploaded_file_factory?: ?string,
     *   psr17_stream_factory?: ?string,
     * } $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

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
            $creatorClass = $this->options['server_request_creator'] ?? ServerRequestCreator::class;
            if (isset($this->options['psr17_server_request_factory'], $this->options['psr17_uri_factory'], $this->options['psr17_uploaded_file_factory'], $this->options['psr17_stream_factory'])) {
                $this->requestCreator = new $creatorClass(
                    new $this->options['psr17_server_request_factory'](),
                    new $this->options['psr17_uri_factory'](),
                    new $this->options['psr17_uploaded_file_factory'](),
                    new $this->options['psr17_stream_factory']()
                );
            } elseif (is_callable($creatorClass)) {
                return $creatorClass();
            } elseif (ServerRequestCreator::class !== $creatorClass) {
                $this->requestCreator = new $creatorClass();
            } else {
                throw new \RuntimeException('You need to specify your PSR-17 classes to create PSR-7 a request');
            }
        }

        return $this->requestCreator->fromGlobals();
    }
}
