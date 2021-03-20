# RoadRunner Runtime for Symfony with nyholm/psr7

A runtime for [RoadRunner](https://roadrunner.dev/)

## Installation

```
composer require runtime/roadrunner-symfony-nyholm
```

## Usage

Define the environment variable `APP_RUNTIME` for your application.

```
APP_RUNTIME=Runtime\RoadRunnerSymfonyNyholm\Runtime
```


```php
// public/index.php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};

```

