<?php

namespace App\Action\Mitglied;

use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final class MitgliedDetailsAction
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
        $mitglied = $this->mitgliedRepo->findById($mitgliedId);

        $data = [
            'mitglied' => $mitglied,
            'titel' => 'Mitglied Details',
        ];

        return $this->view->render($response, 'mitglieder/details.twig', $data);
    }
}
