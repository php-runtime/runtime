<?php

namespace Runtime\Bref;

use Bref\Context\Context;
use Bref\Event\Handler;
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
        echo $this->handler->handle($this->data, new Context('', 0, '', ''));

        return 0;
    }
}
