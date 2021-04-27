<?php

/**
 * Copyright 2021 Google LLC.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\CloudFunctions\Tests;

use Google\CloudFunctions\CloudEvent;
use PHPUnit\Framework\TestCase;

/**
 * @group gcf-framework
 */
class CloudEventTest extends TestCase
{
    public function testJsonSerialize()
    {
        $event = new CloudEvent(
            '1413058901901494',
            '//pubsub.googleapis.com/projects/MY-PROJECT/topics/MY-TOPIC',
            '1.0',
            'com.google.cloud.pubsub.topic.publish',
            'application/json',
            'type.googleapis.com/google.logging.v2.LogEntry',
            'My Subject',
            '2020-12-08T20:03:19.162Z',
            [
                "message" => [
                    "data" => "SGVsbG8gdGhlcmU=",
                    "messageId" => "1408577928008405",
                    "publishTime" => "2020-08-06T22:31:14.536Z"
                ],
                "subscription" => "projects/MY-PROJECT/subscriptions/MY-SUB"
            ]
        );

        $want = '{
    "id": "1413058901901494",
    "source": "\/\/pubsub.googleapis.com\/projects\/MY-PROJECT\/topics\/MY-TOPIC",
    "specversion": "1.0",
    "type": "com.google.cloud.pubsub.topic.publish",
    "datacontenttype": "application\/json",
    "dataschema": "type.googleapis.com\/google.logging.v2.LogEntry",
    "subject": "My Subject",
    "time": "2020-12-08T20:03:19.162Z",
    "data": {
        "message": {
            "data": "SGVsbG8gdGhlcmU=",
            "messageId": "1408577928008405",
            "publishTime": "2020-08-06T22:31:14.536Z"
        },
        "subscription": "projects\\/MY-PROJECT\\/subscriptions\\/MY-SUB"
    }
}';

        $this->assertEquals(json_encode($event, JSON_PRETTY_PRINT), $want);
    }
}
