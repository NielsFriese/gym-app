<?php

namespace App\Action\Mitglied;

use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final class MitgliederListeAction
{
    private MitgliedRepository $mitgliedRepo;

    private Twig $view;

    public function __construct(MitgliedRepository $mitgliedRepo, Twig $twig)
    {
        $this->mitgliedRepo = $mitgliedRepo;
        $this->view = $twig;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $mitglieder = $this->mitgliedRepo->findAll();

        $data = [
            'mitglieder' => $mitglieder,
            'titel' => 'Mitgliederliste',
        ];

        return $this->view->render($response, 'mitglieder/liste.twig', $data);
    }
}
