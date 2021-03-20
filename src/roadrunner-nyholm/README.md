# RoadRunner Runtime with nyholm/psr7

A runtime for [RoadRunner](https://roadrunner.dev/)

## Installation

```
composer require runtime/roadrunner-nyholm
```

## Usage

Define the environment variable `APP_RUNTIME` for your application.

```
APP_RUNTIME=Runtime\RoadRunnerNyholm\Runtime
```

### PSR-7

```php
// public/index.php

use Psr\Http\Message\ServerRequestInterface;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function () {
    return function (ServerRequestInterface $request) {
        return new \Nyholm\Psr7\Response(200, [], 'Hello PSR-7');
    };
};

```

### PSR-15

```php
// public/index.php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

class Application implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new \Nyholm\Psr7\Response(200, [], 'Hello PSR-15');
    }
}

return function () {
    return new Application();
};
```

