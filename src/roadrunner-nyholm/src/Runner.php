<?php

namespace Runtime\RoadRunnerNyholm;

use Nyholm\Psr7;
use Spiral\RoadRunner;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Runner implements RunnerInterface
{
    private $application;

    public function __construct(callable $application)
    {
        $this->application = $application;
    }

    public function run(): int
    {
        $worker = RoadRunner\Worker::create();
        $psrFactory = new Psr7\Factory\Psr17Factory();
        $worker = new RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);
        $app = $this->application;

        while ($request = $worker->waitRequest()) {
            try {
                $response = $app($request);
                $worker->respond($response);
            } catch (\Throwable $e) {
                $worker->getWorker()->error((string) $e);
            }
        }

        return 0;
    }
}
