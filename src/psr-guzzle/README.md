# PSR-7 and PSR-15 Runtime

A runtime with `guzzlehttp/psr7`.

If you are new to the Symfony Runtime component, read more in the [main readme](https://github.com/php-runtime/runtime).

## Installation

```
composer require runtime/psr-guzzle
```

## Usage

This runtime is discovered automatically. You can force your application to use
this runtime by defining the environment variable `APP_RUNTIME`.

```
APP_RUNTIME=Runtime\PsrGuzzle\Runtime
```

### PSR-7

```php
// public/index.php

use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (ServerRequestInterface $request) {
    return new Psr7\Response(200, [], 'PSR-7');
};
```

### PSR-15

```php
// public/index.php

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

class Application implements RequestHandlerInterface {
    // ...
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Psr7\Response(200, [], 'PSR-15');
    }
}

return function (array $context) {
    return new Application($context['APP_ENV'] ?? 'dev');
};
```
