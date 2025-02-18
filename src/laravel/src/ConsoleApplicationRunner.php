<?php

namespace Runtime\Laravel;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ConsoleApplicationRunner implements RunnerInterface
{
    private $application;
    private $input;
    private $output;

    public function __construct(ConsoleKernel $application, InputInterface $input, ?OutputInterface $output = null)
    {
        $this->application = $application;
        $this->input = $input;
        $this->output = $output;
    }

    public function run(): int
    {
        return $this->application->handle($this->input, $this->output);
    }
}
