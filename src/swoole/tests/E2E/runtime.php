<?php

namespace Runtime\Swoole\Tests\E2E;

use Runtime\Swoole\Runtime;
use Swoole\Constant;
use Swoole\Http\Request;
use Swoole\Http\Response;

require_once __DIR__.'/../../vendor/autoload.php';

$options = [
    'port' => 8001,
    'mode' => SWOOLE_BASE,
    'settings' => [
        Constant::OPTION_WORKER_NUM => 1,
        Constant::OPTION_ENABLE_STATIC_HANDLER => true,
        Constant::OPTION_DOCUMENT_ROOT => __DIR__.'/static',
    ],
];

$runtime = new Runtime($options);

$app = static function (Request $request, Response $response): void {
    $response->end();
};

$runner = $runtime->getRunner($app);

echo "Exec: phpunit --testsuit E2E\n";
$runner->run();
