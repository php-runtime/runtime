<?php

namespace Runtime\GoogleCloud\Tests;

use Google\CloudFunctions\CloudEvent;
use PHPUnit\Framework\TestCase;
use Runtime\GoogleCloud\Runtime;
use Symfony\Component\Runtime\RuntimeInterface;

class RuntimeTest extends TestCase
{
    public function testStructuredType()
    {
        $input = [
            'id' => '1234567890',
            'source' => '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            'specversion' => '1.0',
            'type' => 'com.google.cloud.pubsub.topic.publish',
        ];

        $runtime = $this->getRuntimeMock();
        $runtime->method('getBody')->willReturn(json_encode($input));
        $runtime->method('getHeaders')->willReturn(['content-type' => 'application/cloudevents+json']);

        $output = $this->invokeCreateCloudEvent($runtime);
        $this->assertInstanceOf(CloudEvent::class, $output);
        $this->assertSame('1234567890', $output->getId());
        $this->assertSame('com.google.cloud.pubsub.topic.publish', $output->getType());
    }

    public function testLegacyType()
    {
        $input = [
            'data' => 'foo',
            'context' => [
                'eventId' => '1413058901901494',
                'timestamp' => '2020-12-08T20:03:19.162Z',
                'eventType' => 'providers/cloud.pubsub/eventTypes/topic.publish',
                'resource' => [
                    'name' => 'projects/MY-PROJECT/topics/MY-TOPIC',
                    'service' => 'pubsub.googleapis.com',
                ],
            ],
        ];

        $runtime = $this->getRuntimeMock();
        $runtime->method('getBody')->willReturn(json_encode($input));
        $runtime->method('getHeaders')->willReturn([]);

        $output = $this->invokeCreateCloudEvent($runtime);
        $this->assertInstanceOf(CloudEvent::class, $output);
        $this->assertSame(['message' => 'foo'], $output->getData());
        $this->assertSame('1413058901901494', $output->getId());
        $this->assertSame('google.cloud.pubsub.topic.v1.messagePublished', $output->getType());
    }

    public function testValidateJsonWithJsonContentType()
    {
        $runtime = $this->getRuntimeMock();
        $runtime->method('getBody')->willReturn('not json');
        $runtime->method('getHeaders')->willReturn(['content-type' => 'application/json']);

        $this->expectException(ExecutionStopped::class);
        $this->invokeCreateCloudEvent($runtime);
    }

    public function testValidateJsonWithStructuredType()
    {
        $runtime = $this->getRuntimeMock();
        $runtime->method('getBody')->willReturn('not json');
        $runtime->method('getHeaders')->willReturn(['content-type' => 'application/cloudevents+json']);

        $this->expectException(ExecutionStopped::class);
        $this->invokeCreateCloudEvent($runtime);
    }

    public function testValidateJsonWithLegacyType()
    {
        $runtime = $this->getRuntimeMock();
        $runtime->method('getBody')->willReturn('not json');
        $runtime->method('getHeaders')->willReturn([]);

        $this->expectException(ExecutionStopped::class);
        $this->invokeCreateCloudEvent($runtime);
    }

    public function testNoValidateJsonWithBinaryType()
    {
        $runtime = $this->getRuntimeMock();
        $runtime->method('getBody')->willReturn('not json');
        $runtime->method('getHeaders')->willReturn([
            'ce-id' => '1234567890',
            'ce-source' => '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            'ce-specversion' => '1.0',
            'ce-type' => 'com.google.cloud.pubsub.topic.publish',
        ]);

        $output = $this->invokeCreateCloudEvent($runtime);
        $this->assertInstanceOf(CloudEvent::class, $output);
        $this->assertSame('not json', $output->getData());
    }

    private function invokeCreateCloudEvent(RuntimeInterface $runtime): ?CloudEvent
    {
        $reflection = new \ReflectionObject($runtime);
        $method = $reflection->getMethod('createCloudEvent');
        $method->setAccessible(true);

        return $method->invoke($runtime);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Runtime
     */
    private function getRuntimeMock()
    {
        $runtime = $this->getMockBuilder(Runtime::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBody', 'getHeaders', 'sendHttpResponseAndExit'])
            ->getMock();
        $runtime->method('sendHttpResponseAndExit')->willThrowException(new ExecutionStopped());

        return $runtime;
    }
}
