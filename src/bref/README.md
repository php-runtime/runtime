# Bref Runtime

Here is [an example application](https://github.com/Nyholm/bref-runtime-demo).
With this runtime you may use the exact same application for in local development
and in production. Another benefit is that we will not use PHP-FPM.

If you are new to the Symfony Runtime component, read more in the [main readme](https://github.com/php-runtime/runtime).

## Installation

```
composer require runtime/bref
```

Define the environment variable `APP_RUNTIME` for your application on Lambda.

```diff
 # serverless.yml

 # ...

 provider:
     name: aws
     runtime: provided.al2
     memorySize: 1792
     environment:
         APP_ENV: prod
+        APP_RUNTIME: Runtime\Bref\Runtime
+        BREF_LOOP_MAX: 100  # Optional
```

## How to use

You need a new "bootstrap" file for AWS Lambda. That file should look like
[this](https://github.com/brefphp/extra-php-extensions/blob/master/layers/symfony-runtime/bootstrap).
The simplest way is to use the `${bref-extra:symfony-runtime-php-80}` from the
[bref/extra-php-extension package](https://github.com/brefphp/extra-php-extensions).

```
composer require bref/extra-php-extensions
```

```diff
 # serverless.yml

 # ...

 plugins:
    - ./vendor/bref/bref
+   - ./vendor/bref/extra-php-extensions

 functions:
     app:
         handler: public/index.php
         timeout: 8
         layers:
             - ${bref:layer.php-80}
+            - ${bref-extra:symfony-runtime-php-80}
         events:
             -   httpApi: '*'
```

(You may also copy that bootstrap file yourself and place it in your project root.)

### Symfony application

Use the standard Symfony 5.3+ `public/index.php`.

```php
// public/index.php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

```diff
 # serverless.yml

 functions:
     web:
         handler: public/index.php
         timeout: 28
         layers:
-            - ${bref:layer.php-80-fpm}
+            - ${bref:layer.php-80}
+            - ${bref-extra:symfony-runtime-php-80}
         events:
             - httpApi: '*'
```


### PSR-15 application

```php
// public/index.php

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function () {
    return new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            $name = $request->getQueryParams()['name'] ?? 'world';

            return new Response(200, [], "Hello $name");
        }
    };
};
```

### PSR-11 Container

```php
// public/index.php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel =  new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();

    return $kernel->getContainer();
};
```

To define what service to fetch form the container, one need to do one small change
to serverless.yml

```diff
 # serverless.yml

 functions:
     app:
-         handler: public/index.php
+         handler: public/index.php:App\Lambda\MyLambda
```

#### Invoke handlers locally

Using a service from the container makes the handlers very simple to unit test.
However, if you are lazy, you may want to invoke them locally from CLI.

Run the following command to invoke the `App\Lambda\MyLambda` service.

```cli
./vendor/bin/bref-local-handler.php ./bin/container.php:App\\Lambda\\MyLambda
```

If your service expects some event data, add it as a JSON string or a path to a
file containing JSON.

```cli
./vendor/bin/bref-local-handler.php ./bin/container.php:App\\Lambda\\MyLambda '{"foo":"bar"}'

./vendor/bin/bref-local-handler.php ./bin/container.php:App\\Lambda\\MyLambda example/input.json
```

### Console application

Use the standard Symfony 5.3+ `bin/console`.

```php
// bin/console

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new Application($kernel);
};
```

```diff
 # serverless.yml

 functions:
     console:
         handler: bin/console
         timeout: 120
         layers:
             - ${bref:layer.php-80}
-            - ${bref:layer.console}
+            - ${bref-extra:symfony-runtime-php-80}
         events:
             - schedule:
                 rate: rate(30 minutes)
                 input: '"app:invoice:send"'
```
