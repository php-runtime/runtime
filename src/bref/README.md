# Bref Runtime

Here is [an example application](https://github.com/Nyholm/bref-runtime-demo).
With this runtime you may use the exact same application for in local development
and in production. Another benefit is that we will not use PHP-FPM.

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

You need the extra lambda layer `arn:aws:lambda:eu-central-1:403367587399:layer:bref-symfony-runtime:3`
in serverless.yml.

```yaml
# serverless.yml

# ...

functions:
    app:
        handler: public/index.php
        timeout: 8
        layers:
            - ${bref:layer.php-74}
            - arn:aws:lambda:eu-central-1:403367587399:layer:bref-symfony-runtime:3
        events:
            -   httpApi: '*'
```

### Symfony application

```php
// public/index.php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
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
+         handler: public/index.php:App\Service\MyHandler
```

### Console application

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
     app:
-         handler: public/index.php
+         handler: bin/console
```
