# PSR-7 and PSR-15 Runtime

A runtime with `nyholm/psr-7`.

## Installation

```
composer require runtime/psr-nyholm
```

## Usage

This runtime is discovered automatically. You can force your application to use
this runtime by defining the environment variable `APP_RUNTIME`.

```
APP_RUNTIME=Runtime\PsrNyholm\Runtime
```

### PSR-7

```php
// public/index.php

use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7;

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
use Nyholm\Psr7;

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
