# Bref Runtime

Deploy an application with AWS Lambda using Bref.

We support all kinds of applications. See the following sections for details.

1. [Installation and usage](#installation)
1. [Symfony application](#symfony-application)
1. [Laravel application](#laravel-application)
1. [PSR-15 application](#psr-15-application)
1. [Console application](#console-application)
1. [PSR-11 container application](#psr-11-container)
    1. [Invoke handlers locally](#invoke-handlers-locally)
    1. [Simplify serverless.yml](#simplify-serverlessyml)
    1. [Typed handlers](#typed-handlers)
    1. [Symfony Messenger integration](#symfony-messenger-integration)

If you are new to the Symfony Runtime component, read more in the
[main readme](https://github.com/php-runtime/runtime).

## Installation

```
composer require runtime/bref
```

To get started, we need a `serverless.yml` file in our projects root. We also
use the `./vendor/runtime/bref-layer` plugin. Now we can tell AWS that we want
to use a "layer" called `${runtime-bref:php-80}` to run our application on.

Next we need to define the environment variable `APP_RUNTIME` so the Runtime component
knows what runtime to use.

```yaml
# serverless.yml
service: my-app-name

plugins:
    - ./vendor/runtime/bref-layer # <----- Add the extra Serverless plugin

provider:
    name: aws
    region: eu-central-1
    runtime: provided.al2
    memorySize: 1792
    environment:
       APP_ENV: prod
       APP_RUNTIME: Runtime\Bref\Runtime
       BREF_LOOP_MAX: 100 # Optional


functions:
    website:
        handler: public/index.php
        layers:
            - ${runtime-bref:php-80}
        events:
            # Specify that we want HTTP requests
            -   httpApi: '*'
```

That is really it!

We use this file for all kinds of applications. The only thing we change in the
`events`. Imagine that we want to listen to changes to S3 or new SQS messages.
Or we could let this function to be invoked by another application in our system
using a `Bref\Event\Handler`.

## Symfony application

You need some extra features from Bref. Install it with

```
composer req bref/bref
```

Use the standard Symfony 5.3+ `public/index.php`.

```php
// public/index.php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

There is nothing special you need to do.

Here is [an example application](https://github.com/Nyholm/bref-runtime-demo).
With this runtime you may use the exact same application for in local development
and in production.


## Laravel application

You need some extra features from Bref. Install it with

```
composer req bref/bref
```

To run a Laravel application, you need to update your front controller to look
similar to this:

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

Now you are up and running on AWS Lambda!

See [`runtime/laravel`](https://github.com/php-runtime/laravel) on how to run
this locally.


## PSR-15 application

You need some extra features from Bref. Install it with

```
composer req bref/bref
```

Bref is using [`nyholm/psr7`](https://github.com/nyholm/psr7) to provide a PSR-7
and PSR-15 experience. See [`runtime/psr-nyholm`](https://github.com/php-runtime/psr-nyholm)
how to run your application locally.

The following code is an example PSR-15 application with the Runtime component.
If it works locally it will also with on AWS Lambda.

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

## Console application

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

```yaml
# serverless.yml

# ...

functions:
    console:
        handler: bin/console
        timeout: 120
        layers:
           - ${runtime-bref:php-80}
        events:
            - schedule:
                rate: rate(30 minutes)
                input: '"app:invoice:send"'
```

## PSR-11 Container

The PSR-11 container is great. It really shines in internal microservices where
you dont have to deal with HTTP or security. Your application just call your microservice
using an AWS Lambda api client (ie `aws/aws-sdk-php` or `async-aws/lambda`).
It is also great for reacting to S3 or SQS events.

The first thing we need is a file that returns a PSR-11 container. See below
for an example for Symfony.

```php
// bin/container.php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel =  new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();

    return $kernel->getContainer();
};
```

Now we write a class/service that implements `Bref\Event\Handler`.

```php
namespace App\Lambda;

use Bref\Context\Context;
use Bref\Event\Handler;

class HelloWorld implements Handler
{
    public function handle($event, Context $context)
    {
        return 'Hello ' . $event['name'];
    }
}
```

Now we need to update serverless.yml to say "Use bin/container.php to get the container
and then load service App\Lambda\HelloWorld".

```yaml
# serverless.yml

# ...

functions:
    hello:
        handler: bin/container.php:App\Lambda\HelloWorld
        layers:
            - ${runtime-bref:php-80}

```

When this is deployed it can be invoked by

```
serverless invoke --function hello --data '{"name":"Tobias"}'
```

## Invoke handlers locally

Using a service from the container makes the handlers very simple to unit test.
However, if you are lazy, you may want to invoke them locally from CLI.

Run the following command to invoke the `App\Lambda\HelloWorld` service.

```cli
./vendor/bin/bref-local-handler.php ./bin/container.php:App\\Lambda\\HelloWorld
```

If your service expects some event data, add it as a JSON string or a path to a
file containing JSON.

```cli
./vendor/bin/bref-local-handler.php ./bin/container.php:App\\Lambda\\HelloWorld '{"foo":"bar"}'

./vendor/bin/bref-local-handler.php ./bin/container.php:App\\Lambda\\HelloWorld example/input.json
```

### Simplify serverless.yml

The syntax `handler: bin/container.php:App\Lambda\HelloWorld` might be a bit weird to write,
but you may add an environment variable called `FALLBACK_CONTAINER_FILE` which
includes the file to where we can get the PSR-11 container. This may help the
serverless.yml file to read more natually.

```diff
 # serverless.yml

 provider:
     name: aws
     runtime: provided.al2
     # ...
     environment:
        APP_ENV: prod
        APP_RUNTIME: Runtime\Bref\Runtime
+       FALLBACK_CONTAINER_FILE: bin/container.php
        BREF_LOOP_MAX: 100 # Optional

 functions:
     hello:
-        handler: bin/container.php:App\Lambda\HelloWorld
+        handler: App\Lambda\HelloWorld
         layers:
           - ${runtime-bref:php-80}

```

## Typed handlers

To better integrate with different AWS events, one can use "typed handlers".
These are classes that implements `Bref\Event\Handler` and provides some helper
methods or classes.

To use them, you need to install Bref:

```
composer req bref/bref
```

We use the same PSR-11 configuration from above and write custom handler like:

```php
namespace App\Lambda;

use Bref\Context\Context;
use Bref\Event\S3\S3Event;
use Bref\Event\S3\S3Handler;

class S3FileCreated extends S3Handler
{
    public function handleS3(S3Event $event, Context $context): void
    {
        $bucketName = $event->getRecords()[0]->getBucket()->getName();
        $fileName = $event->getRecords()[0]->getObject()->getKey();

        // do something with the file
    }
}
```

```yaml
# serverless.yml

# ...

functions:
    s3_photos:
        handler: bin/container.php:App\Lambda\S3FileCreated
        layers:
            - ${runtime-bref:php-80}
        events:
            - s3:
                  bucket: photos
                  event: s3:ObjectCreated:*
```

Read more about different typed handlers at [Bref's documentation](https://bref.sh/docs/function/handlers.html).

### Symfony Messenger integration

Similar to the typed handlers above, if you use [`bref/symfony-messenger`](https://github.com/brefphp/symfony-messenger)
you may also want to define a worker function.

```yaml
# serverless.yml

# ...

functions:
    worker:
        handler: bin/container.php:Bref\Symfony\Messenger\Service\Sqs\SqsConsumer
        timeout: 120
        layers:
            - ${runtime-bref:php-80}
        events:
            - sqs:
                  batchSize: 1 # Only 1 item at a time to simplify error handling
                  arn: !GetAtt workqueue.Arn

resources:
    Resources:
        workqueue:
            Type: "AWS::SQS::Queue"
            Properties:
                QueueName: ${self:service}-workqueue
                VisibilityTimeout: 600
```
