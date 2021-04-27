<?php

namespace Runtime\GoogleCloud\Tests;

/**
 * An exception to be used in tests to make sure we called `exit()`;.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ExecutionStopped extends \RuntimeException
{
}
