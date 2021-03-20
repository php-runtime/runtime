<?php

namespace Runtime\Laravel;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Runtime\GenericRuntime;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Runtime extends SymfonyRuntime
{
    public function getRunner(?object $application): RunnerInterface
    {
        if ($application instanceof HttpKernel) {
            return new HttpKernelRunner($application, Request::capture());
        }

        if ($application instanceof ConsoleKernel) {
            if (!\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
                echo 'Warning: The console should be invoked via the CLI version of PHP, not the '.\PHP_SAPI.' SAPI'.\PHP_EOL;
            }

            set_time_limit(0);

            return new ConsoleApplicationRunner($application, new ArgvInput(), new ConsoleOutput());
        }

        return parent::getRunner($application);
    }

    protected static function register(GenericRuntime $runtime): GenericRuntime
    {
        $self = new self($runtime->options + ['runtimes' => []]);
        $self->options['runtimes'] += [
            HttpKernel::class => $self,
            ConsoleKernel::class => $self,
        ];
        $runtime->options = $self->options;

        return $self;
    }
}
