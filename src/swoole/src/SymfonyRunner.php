<?php

namespace Runtime\Swoole;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A runner for Symfony.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyRunner implements RunnerInterface
{
    private $application;
    private $options;

    public function __construct(HttpKernelInterface $application, array $options)
    {
        $this->application = $application;
        $this->options = $options;
    }

    public function run(): int
    {
        $server = new Server($this->options['host'], $this->options['port'], $this->options['mode']);

        $server->set($this->options['settings']);

        $app = $this->application;

        $server->on('workerStart', function (Server $server, int $workerId): void {
           swoole_set_process_name("runtime/swoole worker $workerId");
        });

        $server->on('request', function (Request $request, Response $response) use ($app) {
            // convert to HttpFoundation request
            $sfRequest = new SymfonyRequest(
                $request->get ?? [],
                $request->post ?? [],
                [],
                $request->cookie ?? [],
                $request->files ?? [],
                array_change_key_case($request->server ?? [], CASE_UPPER),
                $request->rawContent()
            );
            $sfRequest->headers = new HeaderBag($request->header);

            $sfResponse = $app->handle($sfRequest);
            foreach ($sfResponse->headers->all() as $name => $value) {
                $response->header($name, $value);
            }
            $response->end($sfResponse->getContent());
        });

        $server->start();

        return 0;
    }
}
