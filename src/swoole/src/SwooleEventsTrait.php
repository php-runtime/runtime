<?php

declare(strict_types=1);

namespace Runtime\Swoole;

use Psr\Container\ContainerInterface;
use Swoole\Constant;
use Swoole\Server;

trait SwooleEventsTrait
{
    private function registerSwooleEvents(Server $server, array $options, ?ContainerInterface $container = null): void
    {
        if (!array_key_exists('server_event_listener_factory', $options)) {
            return;
        }

        $eventListener = $options['server_event_listener_factory']($container);
        if (!$eventListener instanceof SwooleServerEventListenerInterface) {
            return;
        }

        $server->on(Constant::EVENT_START, [$eventListener, 'onStart']);
        $server->on(Constant::EVENT_WORKER_START, [$eventListener, 'onWorkerStart']);
        $server->on(Constant::EVENT_WORKER_STOP, [$eventListener, 'onWorkerStop']);
        $server->on(Constant::EVENT_WORKER_ERROR, [$eventListener, 'onWorkerError']);
        $server->on(Constant::EVENT_WORKER_EXIT, [$eventListener, 'onWorkerExit']);
        $server->on(Constant::EVENT_TASK, [$eventListener, 'onTask']);
        $server->on(Constant::EVENT_FINISH, [$eventListener, 'onFinish']);
        $server->on(Constant::EVENT_SHUTDOWN, [$eventListener, 'onShutdown']);
    }
}
