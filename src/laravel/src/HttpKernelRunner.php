<?php

namespace Runtime\Laravel;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Symfony\Component\Runtime\RunnerInterface;

class HttpKernelRunner implements RunnerInterface
{
    private $kernel;
    private $request;

    public function __construct(Kernel $kernel, Request $request)
    {
        $this->kernel = $kernel;
        $this->request = $request;
    }

    public function run(): int
    {
        $response = $this->kernel->handle($this->request);
        $response->send();

        $this->kernel->terminate($this->request, $response);

        return 0;
    }
}
