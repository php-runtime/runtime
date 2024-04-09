# Swoole Runtime

A runtime for [Swoole](https://www.swoole.com/).

If you are new to the Symfony Runtime component, read more in the [main readme](https://github.com/php-runtime/runtime).

## Installation

```
composer require runtime/swoole
```

## Usage

Define the environment variable `APP_RUNTIME` for your application.

```
APP_RUNTIME=Runtime\Swoole\Runtime
```

### Pure PHP

```php
// public/index.php

use Swoole\Http\Request;
use Swoole\Http\Response;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function () {
    return function (Request $request, Response $response) {
        $response->header("Content-Type", "text/plain");
        $response->end("Hello World\n");
    };
};
```

### Symfony

```php
// public/index.php

use App\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

## Using Options

You can define some configurations using Symfony's Runtime [`APP_RUNTIME_OPTIONS` API](https://symfony.com/doc/current/components/runtime.html#using-options).

| Option | Description | Default |
| --- | --- | --- |
| `host` | The host where the server should binds to (precedes `SWOOLE_HOST` environment variable) | `127.0.0.1` |
| `port` | The port where the server should be listing (precedes `SWOOLE_PORT` environment variable) | `8000` |
| `mode` | Swoole's server mode (precedes `SWOOLE_MODE` environment variable) | `SWOOLE_PROCESS` |
| `settings` | All Swoole's server settings ([wiki.swoole.com/en/#/server/setting](https://wiki.swoole.com/en/#/server/setting) and [wiki.swoole.com/en/#/http_server?id=configuration-options](https://wiki.swoole.com/en/#/http_server?id=configuration-options)) | `[]` |

```php
// public/index.php

use App\Kernel;

$_SERVER['APP_RUNTIME_OPTIONS'] = [
    'host' => '0.0.0.0',
    'port' => 9501,
    'mode' => SWOOLE_BASE,
    'settings' => [
        'worker_num' => swoole_cpu_num() * 2,
        'enable_static_handler' => true,
        'document_root' => dirname(__DIR__) . '/public'
    ],
];

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```
