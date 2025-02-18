<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

require __DIR__.'/autoload.php';

return function (array $context) {
    return new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            return new \Nyholm\Psr7\Response(200, [], 'Hello PSR-15');
        }
    };
};
