<?php

namespace Runtime\Bref\Tests;

use Bref\Context\Context;
use Bref\Event\Handler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Server\Server;
use PHPUnit\Framework\TestCase;
use Runtime\Bref\Lambda\LambdaClient;

/**
 * Tests the communication between `LambdaClient` and the Lambda Runtime HTTP API.
 *
 * The API is mocked using a fake HTTP server.
 */
class LambdaClientTest extends TestCase
{
    /** @var LambdaClient */
    private $lambda;

    protected function setUp(): void
    {
        ob_start();
        Server::start();
        $this->lambda = new LambdaClient('localhost:8126', 'phpunit');
    }

    protected function tearDown(): void
    {
        Server::stop();
        ob_end_clean();
    }

    public function test basic behavior()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $output = $this->lambda->processNextEvent(new class() implements Handler {
            public function handle($event, Context $context)
            {
                return ['hello' => 'world'];
            }
        });

        $this->assertTrue($output);
        $this->assertInvocationResult(['hello' => 'world']);
    }

    public function test handler receives context()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $this->lambda->processNextEvent(new class() implements Handler {
            public function handle($event, Context $context)
            {
                return ['hello' => 'world', 'received-function-arn' => $context->getInvokedFunctionArn()];
            }
        });

        $this->assertInvocationResult([
            'hello' => 'world',
            'received-function-arn' => 'test-function-name',
        ]);
    }

    public function test exceptions in the handler result in an invocation error()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $output = $this->lambda->processNextEvent(new class() implements Handler {
            public function handle($event, Context $context)
            {
                throw new \RuntimeException('This is an exception');
            }
        });

        $this->assertFalse($output);
        $this->assertInvocationErrorResult('RuntimeException', 'This is an exception');
        $this->assertErrorInLogs('RuntimeException', 'This is an exception');
    }

    public function test nested exceptions in the handler result in an invocation error()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $this->lambda->processNextEvent(new class() implements Handler {
            public function handle($event, Context $context)
            {
                throw new \RuntimeException('This is an exception', 0, new \RuntimeException('The previous exception.', 0, new \Exception('The original exception.')));
            }
        });

        $this->assertInvocationErrorResult('RuntimeException', 'This is an exception');
        $this->assertErrorInLogs('RuntimeException', 'This is an exception');
        $this->assertPreviousErrorsInLogs([
            ['errorClass' => 'RuntimeException', 'errorMessage' => 'The previous exception.'],
            ['errorClass' => 'Exception', 'errorMessage' => 'The original exception.'],
        ]);
    }

    public function test an error is thrown if the runtime API returns a wrong response()
    {
        $this->expectExceptionMessage('Failed to fetch next Lambda invocation: The requested URL returned error: 404');
        Server::enqueue([
            new Response( // lambda event
                404, // 404 instead of 200
                [
                    'lambda-runtime-aws-request-id' => 1,
                ],
                '{ "Hello": "world!"}'
            ),
        ]);

        $this->lambda->processNextEvent(new class() implements Handler {
            public function handle($event, Context $context)
            {
            }
        });
    }

    public function test an error is thrown if the invocation id is missing()
    {
        $this->expectExceptionMessage('Failed to determine the Lambda invocation ID');
        Server::enqueue([
            new Response( // lambda event
                200,
                [], // Missing `lambda-runtime-aws-request-id`
                '{ "Hello": "world!"}'
            ),
        ]);

        $this->lambda->processNextEvent(new class() implements Handler {
            public function handle($event, Context $context)
            {
            }
        });
    }

    public function test an error is thrown if the invocation body is empty()
    {
        $this->expectExceptionMessage('Empty Lambda runtime API response');
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => 1,
                ]
            ),
        ]);

        $this->lambda->processNextEvent(new class() implements Handler {
            public function handle($event, Context $context)
            {
            }
        });
    }

    public function test a wrong response from the runtime API turns the invocation into an error()
    {
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => 1,
                ],
                '{ "Hello": "world!"}'
            ),
            new Response(400), // The Lambda API returns a 400 instead of a 200
            new Response(200),
        ]);

        $this->lambda->processNextEvent(new class() implements Handler {
            public function handle($event, Context $context)
            {
                return $event;
            }
        });
        $requests = Server::received();
        $this->assertCount(3, $requests);

        [$eventRequest, $eventFailureResponse, $eventFailureLog] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventFailureResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventFailureResponse->getUri()->__toString());
        $this->assertSame('POST', $eventFailureLog->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/error', $eventFailureLog->getUri()->__toString());

        // Check the lambda result contains the error message
        $error = json_decode((string) $eventFailureLog->getBody(), true);
        $this->assertSame('Error while calling the Lambda runtime API: The requested URL returned error: 400 Bad Request', $error['errorMessage']);

        $this->assertErrorInLogs('Exception', 'Error while calling the Lambda runtime API: The requested URL returned error: 400 Bad Request');
    }

    public function test function results that cannot be encoded are reported as invocation errors()
    {
        $this->givenAnEvent(['hello' => 'world!']);

        $this->lambda->processNextEvent(new class() implements Handler {
            public function handle($event, Context $context)
            {
                return "\xB1\x31";
            }
        });

        $message = <<<ERROR
The Lambda response cannot be encoded to JSON.
This error usually happens when you try to return binary content. If you are writing an HTTP application and you want to return a binary HTTP response (like an image, a PDF, etc.), please read this guide: https://bref.sh/docs/runtimes/http.html#binary-responses
Here is the original JSON error: 'Malformed UTF-8 characters, possibly incorrectly encoded'
ERROR;
        $this->assertInvocationErrorResult('Exception', $message);
        $this->assertErrorInLogs('Exception', $message);
    }

    public function test generic event handler()
    {
        $handler = new class() implements Handler {
            /**
             * @param mixed $event
             *
             * @return mixed
             */
            public function handle($event, Context $context)
            {
                return $event;
            }
        };

        $this->givenAnEvent(['foo' => 'bar']);

        $this->lambda->processNextEvent($handler);

        $this->assertInvocationResult(['foo' => 'bar']);
    }

    /**
     * @param mixed $event
     */
    private function givenAnEvent($event): void
    {
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => '1',
                    'lambda-runtime-invoked-function-arn' => 'test-function-name',
                ],
                json_encode($event)
            ),
            new Response(200), // lambda response accepted
        ]);
    }

    /**
     * @param mixed $result
     */
    private function assertInvocationResult($result)
    {
        $requests = Server::received();
        $this->assertCount(2, $requests);

        [$eventRequest, $eventResponse] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventResponse->getUri()->__toString());
        $this->assertEquals($result, json_decode($eventResponse->getBody()->__toString(), true));
    }

    private function assertInvocationErrorResult(string $errorClass, string $errorMessage)
    {
        $requests = Server::received();
        $this->assertCount(2, $requests);

        [$eventRequest, $eventResponse] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/error', $eventResponse->getUri()->__toString());

        // Check the content of the result of the lambda
        $invocationResult = json_decode($eventResponse->getBody()->__toString(), true);
        $this->assertSame([
            'errorType',
            'errorMessage',
            'stackTrace',
        ], array_keys($invocationResult));
        $this->assertEquals($errorClass, $invocationResult['errorType']);
        $this->assertEquals($errorMessage, $invocationResult['errorMessage']);
        $this->assertIsArray($invocationResult['stackTrace']);
    }

    private function assertErrorInLogs(string $errorClass, string $errorMessage): void
    {
        // Decode the logs from stdout
        $stdout = $this->getActualOutput();

        [$requestId, $message, $json] = explode("\t", $stdout);

        $this->assertSame('Invoke Error', $message);

        // Check the request ID matches a UUID
        $this->assertNotEmpty($requestId);

        $invocationResult = json_decode($json, true);
        unset($invocationResult['previous']);
        $this->assertSame([
            'errorType',
            'errorMessage',
            'stack',
        ], array_keys($invocationResult));
        $this->assertEquals($errorClass, $invocationResult['errorType']);
        $this->assertEquals($errorMessage, $invocationResult['errorMessage']);
        $this->assertIsArray($invocationResult['stack']);
    }

    private function assertPreviousErrorsInLogs(array $previousErrors)
    {
        // Decode the logs from stdout
        $stdout = $this->getActualOutput();

        [, , $json] = explode("\t", $stdout);

        ['previous' => $previous] = json_decode($json, true);
        $this->assertCount(count($previousErrors), $previous);
        foreach ($previous as $index => $error) {
            $this->assertSame([
                'errorType',
                'errorMessage',
                'stack',
            ], array_keys($error));
            $this->assertEquals($previousErrors[$index]['errorClass'], $error['errorType']);
            $this->assertEquals($previousErrors[$index]['errorMessage'], $error['errorMessage']);
            $this->assertIsArray($error['stack']);
        }
    }
}
