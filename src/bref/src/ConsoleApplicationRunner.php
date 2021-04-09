<?php

namespace Runtime\Bref;

use Bref\Context\Context;
use Bref\Runtime\LambdaRuntime;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * This will run normal "symfony console" applications.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ConsoleApplicationRunner implements RunnerInterface
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->application->setAutoExit(false);
    }

    public function run(): int
    {
        $lambda = LambdaRuntime::fromEnvironmentVariable();

        while (true) {
            $lambda->processNextEvent(function ($event, Context $context): array {
                $args = \Clue\Arguments\split((string) $event);
                array_unshift($args, 'command');

                $input = new ArgvInput($args);
                $output = new BufferedOutput();
                $exitCode = $this->application->run($input, $output);

                // Echo the output so that it is written to CloudWatch logs
                echo $output->fetch();

                if ($exitCode > 0) {
                    throw new \Exception('The command exited with a non-zero status code: '.$exitCode);
                }

                return [
                    'exitCode' => $exitCode, // will always be 0
                    'output' => $output->fetch(),
                ];
            });
        }
    }
}
