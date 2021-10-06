<?php

namespace Runtime\Bref\Timeout;

/**
 * Helper class to trigger an exception just before the Lambda times out. This
 * will give the application a chance to shut down.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class Timeout
{
    /** @var bool */
    private static $initialized = false;

    /** @var string|null */
    private static $stackTrace = null;

    /**
     * @internal
     */
    public static function enable(int $remainingTimeInMillis): void
    {
        self::init();

        if (!self::$initialized) {
            return;
        }

        $remainingTimeInSeconds = (int) floor($remainingTimeInMillis / 1000);

        // The script will timeout 2 seconds before the remaining time
        // to allow some time for Bref/our app to recover and cleanup
        $margin = 2;

        $timeoutDelayInSeconds = max(1, $remainingTimeInSeconds - $margin);

        // Trigger SIGALRM in X seconds
        pcntl_alarm($timeoutDelayInSeconds);
    }

    /**
     * Setup custom handler for SIGALRM.
     */
    private static function init(): void
    {
        self::$stackTrace = null;

        if (self::$initialized) {
            return;
        }

        if (!function_exists('pcntl_async_signals')) {
            trigger_error('Could not enable timeout exceptions because pcntl extension is not enabled.');

            return;
        }

        pcntl_async_signals(true);
        // Setup a handler for SIGALRM that throws an exception
        // This will interrupt any running PHP code, including `sleep()` or code stuck waiting for I/O.
        pcntl_signal(SIGALRM, function (): void {
            if (null !== Timeout::$stackTrace) {
                // we have already thrown an exception, do a harder exit.
                error_log('Lambda timed out');
                error_log((new LambdaTimeoutException())->getTraceAsString());
                error_log('Original stack trace');
                error_log(Timeout::$stackTrace);

                exit(1);
            }

            $exception = new LambdaTimeoutException('Maximum AWS Lambda execution time reached');
            Timeout::$stackTrace = $exception->getTraceAsString();

            // Trigger another alarm after 1 second to do a hard exit.
            pcntl_alarm(1);

            throw $exception;
        });

        self::$initialized = true;
    }

    /**
     * Cancel all current timeouts.
     *
     * @internal
     */
    public static function reset(): void
    {
        if (self::$initialized) {
            pcntl_alarm(0);
            self::$stackTrace = null;
        }
    }
}
