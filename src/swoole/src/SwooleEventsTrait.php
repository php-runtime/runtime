<?php

declare(strict_types=1);

namespace Runtime\Swoole;

use Psr\Container\ContainerInterface;
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

        $server->on('start', [$eventListener, 'onStart']);
        $server->on('workerStart', [$eventListener, 'onWorkerStart']);
        $server->on('workerStop', [$eventListener, 'onWorkerStop']);
        $server->on('workerError', [$eventListener, 'onWorkerError']);
        $server->on('workerExit', [$eventListener, 'onWorkerExit']);
        $server->on('task', [$eventListener, 'onTask']);
        $server->on('finish', [$eventListener, 'onFinish']);
        $server->on('shutdown', [$eventListener, 'onShutdown']);
    }
}
