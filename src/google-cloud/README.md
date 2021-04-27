# Google Cloud Runtime

A runtime for [Google Cloud](https://cloud.google.com/).

## Installation

This runtime layer is special. It includes a `router.php` to enable the use of
Symfony Runtime component. You need to install this package **and** the runtime you
want to use.

To use with native or Symfony application.

```
composer require runtime/google-cloud symfony/runtime
```

If you want to use it with PSR-7.
```
composer require runtime/google-cloud runtime/psr-nyholm-laminas
```

## Usage

Define the environment variable `FUNCTION_SOURCE`.

```
# Default value
FUNCTION_SOURCE=index.php
```

Note that Google Cloud **requires** you to have an index.php file. If you are running
Symfony you probably want to define `FUNCTION_SOURCE=public/index.php` but you
still need to create an `index.php`.

```php
<?php
// index.php
// This file is needed for google cloud
```

## Using CloudEvent

```php
// index.php
use Google\CloudFunctions\CloudEvent;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function(CloudEvent $cloudevent) {
    // Print the whole CloudEvent
    $stdout = fopen('php://stdout', 'wb');
    fwrite($stdout, $cloudevent);
};
```

## Troubleshooting

### Cache/Build directory

Note that Google Cloud will only deploy files that are not in `.gitignore`. You
need to remove the `var/` entry before deployment to be able to warm up the cache etc.

### Define Symfony environment

Define environment variable `APP_ENV=prod` to use Symfony production mode.

```
gcloud functions deploy helloHttp \
 --runtime php74 \
 --trigger-http \
 --allow-unauthenticated \
 --set-env-vars "FUNCTION_SOURCE=public/index.php,APP_ENV=prod"
```
