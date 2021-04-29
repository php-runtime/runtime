# PSR-7 and PSR-15 Runtime

A runtime with `laminas/diactoros` and `laminas/laminas-httphandlerrunner`.

If you are new to the Symfony Runtime component, read more in the [main readme](https://github.com/php-runtime/runtime).

## Installation

```
composer require runtime/psr-laminas
```

## Usage

This runtime is discovered automatically. You can force your application to use
this runtime by defining the environment variable `APP_RUNTIME`.

```
APP_RUNTIME=Runtime\PsrLaminas\Runtime
```

### PSR-7

```php
// public/index.php

use Psr\Http\Server\RequestHandlerInterface;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (ServerRequestInterface $request) {
    $response = new \Laminas\Diactoros\Response();
    $response->getBody()->write('PSR-7');

    return $response;
};
```

### PSR-15

```php
// public/index.php

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

class Application implements RequestHandlerInterface {
    // ...
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new \Laminas\Diactoros\Response();
        $response->getBody()->write('PSR-15');

        return $response;
    }
}

return function (array $context) {
    return new Application($context['APP_ENV'] ?? 'dev');
};
```
