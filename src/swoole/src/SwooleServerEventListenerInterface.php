<?php

declare(strict_types=1);

namespace Runtime\Swoole;

use Swoole\Server;

interface SwooleServerEventListenerInterface
{
    public function onStart(Server $server): void;

    public function onShutdown(Server $server): void;

    public function onWorkerStart(Server $server, int $workerId): void;

    public function onWorkerStop(Server $server, int $workerId): void;

    public function onWorkerError(Server $server, int $workerId, int $exitCode, int $signal): void;

    public function onWorkerExit(Server $server, int $workerId): void;

    public function onTask(Server $server, int $taskId, int $srcWorkerId, mixed $data): void;

    public function onFinish(Server $server, int $taskId, $data): void;
}
