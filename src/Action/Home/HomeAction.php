<?php

namespace App\Action\Home;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class HomeAction
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'home.twig', [
            'message' => 'Willkommen im Fitnessstudio-Verwaltungssystem'
        ]);
    }
}
