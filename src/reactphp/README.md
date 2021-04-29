# ReactPHP Runtime

A runtime for [ReactPHP](https://reactphp.org/).

If you are new to the Symfony Runtime component, read more in the [main readme](https://github.com/php-runtime/runtime).

## Installation

```
composer require runtime/react
```

## Usage

Define the environment variable `APP_RUNTIME` for your application.

```
APP_RUNTIME=Runtime\React\Runtime
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
