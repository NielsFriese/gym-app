<?php

namespace App\Action\Home;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HomeAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $wert = $this->nielsNamenHoler();

        $response->getBody()->write($wert);

        return $response;
    }

    private function nielsNamenHoler() :string
    {
        return 'Hallo Niels';
    }
}
