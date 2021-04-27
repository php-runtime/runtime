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

## The long story

This section is for you who are new to Symfony Runtime component.

Symfony Runtime component will be released with Symfony 5.3 in May 2021. Here is
the [official documentation](https://symfony.com/doc/5.3/components/runtime.html)
there is also a compressed version in the [main readme](https://github.com/php-runtime/runtime).

Every Symfony application from Symfony 5.3 will be created with this component as
default. The component makes sure your application is decoupled from the global state.
Which means your application is very portable. With some config (or automatic mapping)
a `RuntimeInterface` is used as the "glue" between Nginx and your application.

Of course, different `RuntimeInterface` "glue" between different things. One Runtime
is for **Google Cloud**, one for **Bref/AWS**, one for **Swoole**, one for **RoadRunner**
etc. The point is that your application does not care what runtime it is. This means
that you can run your application locally with a normal web server (like Nginx) and
deploy it to Google Cloud with zero changes and still be sure everything works.

### Google Cloud Runtime specifically

Since Google Cloud is very similar to a "normal web server", this runtime only
contains 2 things:
1. `router.php` which is a requirement from Google Cloud. Its job is just to redirect
the request to the front controller. It is not used locally.
2. Support for `CloudEvent`. If you write an application that expects a `Google\CloudFunctions\CloudEvent`
this runtime will automatically detect that and create such object for you.

It will support native PHP applications and Symfony HttpFoundation type applications
out-of-the-box. To support PSR-7/PSR-15 or Laravel, one also need to install one additional
runtime. See [main readme](https://github.com/php-runtime/runtime) for more information.
