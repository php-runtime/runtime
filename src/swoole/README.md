# Swoole Runtime

A runtime for [Swoole](https://www.swoole.co.uk/).

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

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

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

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```
