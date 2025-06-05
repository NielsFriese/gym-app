<?php

namespace App\Action\Mitglied;

use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;



final class MitgliedBearbeitenAction
{
    private MitgliedRepository $mitgliedRepo;
    private Twig $view;
    

    public function __construct(MitgliedRepository $mitgliedRepo, Twig $twig )
    {
        $this->mitgliedRepo = $mitgliedRepo;
        $this->view = $twig;
        
    }

    /**
     * Zeigt das Formular zum Bearbeiten eines Mitglieds an.
     */
    public function showEditForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id'];
        $mitglied = $this->mitgliedRepo->findById($mitgliedId);

        if (!$mitglied) {
           
            
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $url = $routeParser->urlFor('mitglieder-liste');
            return $response->withHeader('Location', $url)->withStatus(302);
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $data = [
            'title' => 'Mitglied bearbeiten: ' . htmlspecialchars($mitglied['vorname'] . ' ' . $mitglied['nachname']),
            'formAction' => $routeParser->urlFor('mitglied-bearbeiten-submit', ['id' => $mitgliedId]),
            'mitglied' => $mitglied, // Um das Formular vorab auszufüllen
            'mitgliedschaftsTypen' => ['Basis', 'Premium', 'VIP'], // Wie in deiner ErstellenAction
            'isEditMode' => true, // Flag für das Template
        ];

        return $this->view->render($response, 'mitglieder/formular.twig', $data);
    }

    /**
     * Verarbeitet die gesendeten Daten des Bearbeitungsformulars.
     */
    public function handleEditSubmit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id'];
        $formularDaten = (array)$request->getParsedBody();

        // Hier könntest du Validierungslogik hinzufügen, bevor du die Datenbank aktualisierst
        

        $erfolg = $this->mitgliedRepo->update($mitgliedId, [
            'vorname' => $formularDaten['vorname'] ?? '',
            'nachname' => $formularDaten['nachname'] ?? '',
            'email' => $formularDaten['email'] ?? '',
            'geburtsdatum' => $formularDaten['geburtsdatum'] ?? '',
            'mitgliedschaft_typ' => $formularDaten['mitgliedschaft_typ'] ?? '',
        ]);

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $url = $routeParser->urlFor('mitglieder-liste');

        
        
        return $response->withHeader('Location', $url)->withStatus(302);
    }
}