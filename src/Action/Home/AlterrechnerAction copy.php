<?php

namespace App\Action\Home;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AlterrechnerAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $name = $request->getAttribute('name');
        $jahr = $request->getAttribute('jahr');

        $berechnetetsAlter = $this->calculateAge($jahr);

        $result = "Hey $name, du bist $berechnetetsAlter alt. Ganz schön alt.";
        $response->getBody()->write($result);

        return $response;
    }

    private function calculateAge(int $geburtsjahr) :int
    {
        $heutigesJahr = (int) date('Y');
        $alter = $heutigesJahr - $geburtsjahr;

        return $alter;
    }
}
