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
    public function __construct(private Handler $handler, private mixed $data)
    {
    }

    public function run(): int
    {
        $result = $this->handler->handle($this->data, new Context('', 0, '', ''));

        if (is_array($result)) {
            echo json_encode($result, JSON_PRETTY_PRINT);
        } elseif (is_scalar($result)) {
            echo $result;
        } else {
            echo 'Handler result is of type: '.get_debug_type($result);
        }

        return 0;
    }
}
