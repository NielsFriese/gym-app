<?php

namespace App\Action\Home;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NamenGetter
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $name = $request->getAttribute('name');
        $response->getBody()->write("Hallo $name");

        return $response;
    }
}
