<?php

namespace App\Action\Trainingsplan;

use App\Domain\Trainingsplan\TrainingsplanRepository;
use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

final class TrainingsplanLoeschenAction
{
    private TrainingsplanRepository $trainingsplanRepo;
    private MitgliedRepository $mitgliedRepo;

    public function __construct(
        TrainingsplanRepository $trainingsplanRepo,
        MitgliedRepository $mitgliedRepo
    ) {
        $this->trainingsplanRepo = $trainingsplanRepo;
        $this->mitgliedRepo = $mitgliedRepo;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $planId = (int)$args['plan_id'];
        
        // Trainingsplan mit Grundinformationen abrufen
        $trainingsplan = $this->trainingsplanRepo->findById($planId);
        if (!$trainingsplan) {
            return $response->withStatus(404);
        }
        
        // Mitglied-ID für die Weiterleitung speichern
        $mitgliedId = $trainingsplan['mitglied_id'];
        
        // Prüfen, ob das Mitglied existiert
        $mitglied = $this->mitgliedRepo->findById($mitgliedId);
        if (!$mitglied) {
            return $response->withStatus(404);
        }
        
        // Löschen des Trainingsplans und aller zugehörigen Daten
        // Die Kaskadierung sollte in der Datenbank durch ON DELETE CASCADE sichergestellt sein
        $success = $this->trainingsplanRepo->deleteTrainingsplan($planId);
        
        if (!$success) {
            // Fehler beim Löschen - Fehlermeldung anzeigen
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            return $response
                ->withHeader('Location', $routeParser->urlFor('trainingsplan-anzeigen', ['plan_id' => $planId]) . '?error=delete_failed')
                ->withStatus(302);
        }
        
        // Erfolgreich gelöscht - Weiterleitung zur Trainingsplan-Liste des Mitglieds
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response
            ->withHeader('Location', $routeParser->urlFor('mitglied-trainingsplaene', ['id' => $mitgliedId]) . '?success=deleted')
            ->withStatus(302);
    }
}