<?php

use Psr\Http\Message\ServerRequestInterface;

require __DIR__.'/autoload.php';

return function (ServerRequestInterface $request) {
    return new \Nyholm\Psr7\Response(200, [], 'Hello PSR-7');
};
