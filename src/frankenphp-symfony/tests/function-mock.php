<?php

if (!function_exists('frankenphp_handle_request')) {
    function frankenphp_handle_request(callable $callable): bool
    {
        $callable();

        return false;
    }
}
