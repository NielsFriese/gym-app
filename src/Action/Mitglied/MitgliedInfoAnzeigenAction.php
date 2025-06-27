<?php

namespace App\Action\Mitglied;

use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final class MitgliedInfoAnzeigenAction
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
        $mitglied = $this->mitgliedRepo->findByIdWithInfo($mitgliedId);

        if (!$mitglied) {
            // Mitglied nicht gefunden - 404 Status zurückgeben
            return $response->withStatus(404);
        }

        // Erweiterte Daten für die kombinierte Ansicht
        $data = [
            'title' => 'Mitglied: ' . $mitglied['vorname'] . ' ' . $mitglied['nachname'],
            'mitglied' => $mitglied,
            // Grunddaten explizit für die Ansicht bereitstellen
            'grunddaten' => [
                'mitglied_id' => $mitglied['mitglied_id'],
                'email' => $mitglied['email'],
                'geburtsdatum' => $mitglied['geburtsdatum'],
                'beitrittsdatum' => $mitglied['beitrittsdatum'],
                'mitgliedschaft_typ' => $mitglied['mitgliedschaft_typ']
            ]
        ];

        return $this->view->render($response, 'mitglieder/info-anzeigen.twig', $data);
    }
}