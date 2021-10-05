<?php

namespace Runtime\Bref;

use Bref\Context\Context;
use Bref\Event\Handler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @internal
 */
class ConsoleApplicationHandler implements Handler
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->application->setAutoExit(false);
    }

    public function handle($event, Context $context)
    {
        $args = \Clue\Arguments\split((string) $event);
        array_unshift($args, 'command');

        $input = new ArgvInput($args);
        $output = new BufferedOutput();
        $exitCode = $this->application->run($input, $output);

        $content = $output->fetch();
        // Echo the output so that it is written to CloudWatch logs
        echo $content;

        if ($exitCode > 0) {
            throw new \Exception('The command exited with a non-zero status code: '.$exitCode);
        }

        return [
            'exitCode' => $exitCode, // will always be 0
            'output' => $content,
        ];
    }
}
