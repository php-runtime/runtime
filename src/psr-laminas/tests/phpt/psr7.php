<?php

use Psr\Http\Message\ServerRequestInterface;

require __DIR__.'/autoload.php';

return function (ServerRequestInterface $request) {
    $response = new \Laminas\Diactoros\Response();
    $response->getBody()->write('Hello PSR-7');

    return $response;
};
