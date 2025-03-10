<?php

/**
 * Copyright 2020 Google LLC.
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

namespace Google\CloudFunctions;

class CloudEvent implements \JsonSerializable
{
    final public function __construct(
        // Required Fields
        private string $id,
        private string $source,
        private string $specversion,
        private string $type,
        // Optional Fields
        private ?string $datacontenttype,
        private ?string $dataschema,
        private ?string $subject,
        private ?string $time,
        private mixed $data
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSpecVersion(): string
    {
        return $this->specversion;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDataContentType(): ?string
    {
        return $this->datacontenttype;
    }

    public function getDataSchema(): ?string
    {
        return $this->dataschema;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function getData()
    {
        return $this->data;
    }

    public static function fromArray(array $arr): static
    {
        $args = [];
        $argKeys = [
            'id',
            'source',
            'specversion',
            'type',
            'datacontenttype',
            'dataschema',
            'subject',
            'time',
            'data',
        ];

        foreach ($argKeys as $key) {
            $args[] = $arr[$key] ?? null;
        }

        return new static(...$args);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'specversion' => $this->specversion,
            'type' => $this->type,
            'datacontenttype' => $this->datacontenttype,
            'dataschema' => $this->dataschema,
            'subject' => $this->subject,
            'time' => $this->time,
            'data' => $this->data,
        ];
    }

    public function __toString()
    {
        $output = implode("\n", [
            'CLOUDEVENT metadata:',
            "- id: $this->id",
            "- source: $this->source",
            "- specversion: $this->specversion",
            "- type: $this->type",
            "- datacontenttype: $this->datacontenttype",
            "- dataschema: $this->dataschema",
            "- subject: $this->subject",
            "- time: $this->time",
        ]);

        return $output;
    }
}
