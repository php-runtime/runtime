<?php

namespace Runtime\Bref\Timeout;

/**
 * The application took too long to produce a response. This exception is thrown
 * to give the application a chance to flush logs and shut itself down before
 * the power to AWS Lambda is disconnected.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LambdaTimeoutException extends \RuntimeException
{
}
