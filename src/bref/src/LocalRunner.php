<?php

namespace Runtime\Bref;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Runtime\Invoker;
use Bref\Runtime\LambdaRuntime;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * This will run BrefHandlers for local development.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LocalRunner implements RunnerInterface
{
    private $handler;
    private $data;

    public function __construct(Handler $handler, $data)
    {
        $this->handler = $handler;
        $this->data = $data;
    }

    public function run(): int
    {
        $invoker = new Invoker();
        $invoker->invoke($this->handler, $this->data, new Context('', 0, '', ''));

        return 0;
    }
}
