<?php

namespace App\Action\Mitglied;

use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class MitgliedLoeschenAction
{
    private MitgliedRepository $mitgliedRepo;

    private Twig $view;

    public function __construct(MitgliedRepository $mitgliedRepo, Twig $twig)
    {
        $this->mitgliedRepo = $mitgliedRepo;
        $this->view = $twig;
    }


    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id']; 

        $erfolg = $this->mitgliedRepo->delete($mitgliedId);

        
    }
}