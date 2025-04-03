<?php

namespace App\Action\Home;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HalloAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $grösse = $request->getAttribute('grösse');
        $gewicht = $request->getAttribute('gewicht');

        $bmi = $this->berechneBmi($grösse, $grösse);

        $result = "Hey dein BMI ist $bmi ";
        $response->getBody()->write($result);

        return $response;
    }

    private function berechneBMI(float $grösse, float $gewicht): float
    {
        return  $gewicht / ($grösse * $grösse);
    }
}
