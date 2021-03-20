<?php

namespace Runtime\Swoole;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request as LaravelRequest;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * A runner for Laravel.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LaravelRunner implements RunnerInterface
{
    private $application;
    private $port;
    private $host;

    public function __construct(Kernel $application, $host, $port)
    {
        $this->application = $application;
        $this->host = $host;
        $this->port = $port;
    }

    public function run(): int
    {
        $server = new Server($this->host, $this->port);

        $app = $this->application;

        $server->on('request', function (Request $request, Response $response) use ($app) {
            // convert to HttpFoundation request
            $sfRequest = new LaravelRequest(
                $request->get ?? [],
                $request->post ?? [],
                [],
                $request->cookie ?? [],
                $request->files ?? [],
                $request->server ?? [],
                $request->rawContent()
            );

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
