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
    public function __construct(private ConsoleKernel $application, private InputInterface $input, private ?OutputInterface $output = null)
    {
    }

    public function run(): int
    {
        return $this->application->handle($this->input, $this->output);
    }
}
