# PSR-7 and PSR-15 Runtime

A runtime with support for any PSR-17 compatible implementation.

If you are new to the Symfony Runtime component, read more in the [main readme](https://github.com/php-runtime/runtime).

## Installation

Install this runtime plus a package that [provide psr/http-factory-implementation](https://packagist.org/providers/psr/http-factory-implementation).

```
composer require runtime/psr-17 slim/psr7
```

Also update your composer.json with some extra config:

```json
{
    "require": {
        "...": "..."
    },
    "extra": {
        "runtime": {
            "psr17_server_request_factory": "Slim\\Psr7\\Factory\\ServerRequestFactory"
            "psr17_uri_factory": "Slim\\Psr7\\Factory\\UriFactory"
            "psr17_uploaded_file_factory": "Slim\\Psr7\\Factory\\UploadedFileFactory"
            "psr17_stream_factory": "Slim\\Psr7\\Factory\\StreamFactory"
        }
    }
}
```

## Usage

This runtime is discovered automatically. You can force your application to use
this runtime by defining the environment variable `APP_RUNTIME`.

```
APP_RUNTIME=Runtime\Psr17\Runtime
```

### PSR-7

```php
// public/index.php

use Psr\Http\Message\ServerRequestInterface;
use Any\Psr7;

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
use Any\Psr7;

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
