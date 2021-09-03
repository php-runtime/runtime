#!/usr/bin/env php
<?php

if ($argc < 2) {
    error_log("bref-local-handler.php must be invoked with at least one argument: \n./vendor/bin/bref-local-handler.php ./bin/container.php:App\\Service\\MyService [data or file path]");
    exit(1);
}

/*
 * Set the correct runtime
 */
if (!isset($_SERVER['APP_RUNTIME'])) {
    $_SERVER['APP_RUNTIME'] = \Runtime\Bref\Runtime::class;
}

$config = [
    'bref_runner_type' => 'local',
];

/*
 * Handle first argument and prepare "handler"
 */
$handler = $argv[1];
if (!isset($_SERVER['_HANDLER'])) {
    $_SERVER['_HANDLER'] = $handler;
}

if (false === $pos = strpos($handler, ':')) {
    $file = $handler;
} else {
    $file = substr($handler, 0, $pos);
}
$_SERVER['SCRIPT_FILENAME'] = $file;

/*
 * Handle second argument which is that data or a file with the data
 */
if (isset($argv[2])) {
    $json = $argv[2];
    if (is_file($json)) {
        $json = file_get_contents($json);
    }

    try {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new Exception('The JSON provided for the event data is invalid JSON.');
    }

    $config['bref_local_runner_data'] = $data;
}

/*
 * Dump all options to APP_RUNTIME_OPTIONS
 */
$_SERVER['APP_RUNTIME_OPTIONS'] = array_merge($config, $_SERVER['APP_RUNTIME_OPTIONS'] ?? []);

include $file;
