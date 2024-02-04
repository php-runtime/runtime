<?php

namespace Runtime\Swoole\Tests\E2E;

use Runtime\Swoole\Runtime;
use Runtime\Swoole\SwooleServerEventListenerInterface;
use Swoole\Constant;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;

require_once __DIR__.'/../../vendor/autoload.php';

$eventListener = new class() implements SwooleServerEventListenerInterface {
    #[\Override]
    public function onStart(Server $server): void
    {
        echo '-- onServerStart --'.PHP_EOL;
        echo "Manager PID: {$server->manager_pid}".PHP_EOL;

        go(function () use ($server) {
            $server->task(['data' => 'Hello World']);
        });
    }

    #[\Override]
    public function onShutdown(Server $server): void
    {
        echo '-- onServerShutdown --'.PHP_EOL;
    }

    #[\Override]
    public function onWorkerStart(Server $server, int $workerId): void
    {
        echo '-- onWorkerStart --'.PHP_EOL;
    }

    #[\Override]
    public function onWorkerStop(Server $server, int $workerId): void
    {
        echo '-- onWorkerStop --'.PHP_EOL;
    }

    #[\Override]
    public function onWorkerError(Server $server, int $workerId, int $exitCode, int $signal): void
    {
        echo '-- onWorkerError --'.PHP_EOL;
    }

    #[\Override]
    public function onWorkerExit(Server $server, int $workerId): void
    {
        echo '-- onWorkerExit --'.PHP_EOL;
    }

    #[\Override]
    public function onTask(Server $server, int $taskId, int $srcWorkerId, mixed $data): void
    {
        echo '-- onTask --'.PHP_EOL;
        echo 'task payload: '.json_encode($data).PHP_EOL;
        $server->finish($data);
    }

    #[\Override]
    public function onFinish(Server $server, int $taskId, $data): void
    {
        echo '-- onTaskFinish --'.PHP_EOL;
    }
};
$options = [
    'port' => 8001,
    'mode' => SWOOLE_BASE,
    'settings' => [
        Constant::OPTION_WORKER_NUM => 1,
        Constant::OPTION_TASK_WORKER_NUM => 1,
        Constant::OPTION_ENABLE_STATIC_HANDLER => true,
        Constant::OPTION_DOCUMENT_ROOT => __DIR__.'/static',
    ],
    'server_event_listener_factory' => fn () => $eventListener,
];

$runtime = new Runtime($options);

$app = static function (Request $request, Response $response): void {
    $response->end();
};

$runner = $runtime->getRunner($app);

echo "Exec: phpunit --testsuit E2E\n";
$runner->run();
