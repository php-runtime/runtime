<?php

/**
 * To support use of runtime/bref without bref/bref we include two small
 * classes here.
 */
if (!class_exists(\Bref\Context\Context::class)) {
    require_once __DIR__.'/Context.php';
}

if (!interface_exists(\Bref\Event\Handler::class)) {
    require_once __DIR__.'/Handler.php';
}
