# Laravel Runtime

A runtime for [Laravel](https://laravel.com/).

## Installation

```
composer require runtime/laravel
```

## Usage

The runtime will register automatically. You may "force" the runtime by defining
the environment variable `APP_RUNTIME` for your application.

```
APP_RUNTIME=Runtime\Laravel\Runtime
```

### Front controller

```php
// public/index.php

use Illuminate\Contracts\Http\Kernel;

define('LARAVEL_START', microtime(true));

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (): Kernel {
    static $app;

    if (null === $app) {
        $app = require dirname(__DIR__).'/bootstrap/app.php';
    }

    return $app->make(Kernel::class);
};
```

### Artisan

```php
// artisan

use Illuminate\Contracts\Console\Kernel;

define('LARAVEL_START', microtime(true));

require_once __DIR__.'/vendor/autoload_runtime.php';

return function (): Kernel {
    static $app;

    if (null === $app) {
        $app = require __DIR__.'/bootstrap/app.php';
    }

    return $app->make(Kernel::class);
};
```
