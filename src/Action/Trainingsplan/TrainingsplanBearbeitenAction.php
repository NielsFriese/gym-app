<?php

namespace App\Action\Trainingsplan;

use App\Domain\Trainingsplan\TrainingsplanRepository;
use App\Domain\Mitglied\MitgliedRepository;
use App\Domain\Uebungen\UebungRepository;
use App\Domain\Maximalkraft\MaximalkraftTestRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class TrainingsplanBearbeitenAction
{
    private TrainingsplanRepository $trainingsplanRepo;
    private MitgliedRepository $mitgliedRepo;
    private UebungRepository $uebungRepo;
    private MaximalkraftTestRepository $maximalkraftRepo;
    private Twig $view;

    public function __construct(
        TrainingsplanRepository $trainingsplanRepo,
        MitgliedRepository $mitgliedRepo,
        UebungRepository $uebungRepo,
        MaximalkraftTestRepository $maximalkraftRepo,
        Twig $twig
    ) {
        $this->trainingsplanRepo = $trainingsplanRepo;
        $this->mitgliedRepo = $mitgliedRepo;
        $this->uebungRepo = $uebungRepo;
        $this->maximalkraftRepo = $maximalkraftRepo;
        $this->view = $twig;
    }

    public function showEditForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $planId = (int)$args['plan_id'];
        
        // Trainingsplan mit Grundinformationen abrufen
        $trainingsplan = $this->trainingsplanRepo->findById($planId);
        if (!$trainingsplan) {
            return $response->withStatus(404);
        }
        
        // Mitgliedsinformationen abrufen
        $mitglied = $this->mitgliedRepo->findByIdWithInfo($trainingsplan['mitglied_id']);
        if (!$mitglied) {
            return $response->withStatus(404);
        }
        
        // Alle Übungen des Trainingsplans abrufen
        $planUebungen = $this->trainingsplanRepo->findUebungenByPlanId($planId);
        
        // Übungen nach Trainingstagen gruppieren
        $uebungenNachTag = [];
        foreach ($planUebungen as $uebung) {
            $tag = $uebung['trainingstag'];
            if (!isset($uebungenNachTag[$tag])) {
                $uebungenNachTag[$tag] = [];
            }
            
            // Details für jede Übung abrufen (Sätze, Wiederholungen, Gewichte)
            $uebung['details'] = $this->trainingsplanRepo->findUebungDetailsByUebungId($uebung['trainingsplan_uebung_id']);
            $uebungenNachTag[$tag][] = $uebung;
        }
        
        // Alle verfügbaren Übungen abrufen für die Auswahl von Ersatzübungen
        $alleUebungen = $this->uebungRepo->findAll();
        
        // Gruppieren der Übungen nach Muskelgruppen für bessere Übersicht
        $uebungenNachMuskelgruppe = [];
        foreach ($alleUebungen as $uebung) {
            $muskelgruppe = $uebung['muskelgruppe'];
            if (!isset($uebungenNachMuskelgruppe[$muskelgruppe])) {
                $uebungenNachMuskelgruppe[$muskelgruppe] = [];
            }
            $uebungenNachMuskelgruppe[$muskelgruppe][] = $uebung;
        }
        
        // Maximalkraft-Tests des Mitglieds abrufen
        $maximalkraftTests = $this->maximalkraftRepo->findLatestTestsPerExercise($mitglied['mitglied_id']);
        
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        
        $data = [
            'title' => 'Trainingsplan bearbeiten: ' . $trainingsplan['plan_name'],
            'trainingsplan' => $trainingsplan,
            'mitglied' => $mitglied,
            'uebungenNachTag' => $uebungenNachTag,
            'alleUebungen' => $alleUebungen,
            'uebungenNachMuskelgruppe' => $uebungenNachMuskelgruppe,
            'maximalkraftTests' => $maximalkraftTests,
            'submitUrl' => $routeParser->urlFor('trainingsplan-bearbeiten-submit', ['plan_id' => $planId]),
            'backUrl' => $routeParser->urlFor('trainingsplan-anzeigen', ['plan_id' => $planId])
        ];
        
        return $this->view->render($response, 'trainingsplan/bearbeiten-form.twig', $data);
    }

    public function handleEditSubmit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $planId = (int)$args['plan_id'];
        $data = $request->getParsedBody();
        
        // Trainingsplan mit Grundinformationen abrufen
        $trainingsplan = $this->trainingsplanRepo->findById($planId);
        if (!$trainingsplan) {
            return $response->withStatus(404);
        }
        
        // Grunddaten des Trainingsplans aktualisieren
        $planName = $data['plan_name'] ?? $trainingsplan['plan_name'];
        $schwierigkeitsgrad = $data['schwierigkeitsgrad'] ?? $trainingsplan['schwierigkeitsgrad'];
        $trainingsart = $data['trainingsart'] ?? $trainingsplan['trainingsart'];
        $notizen = $data['notizen'] ?? $trainingsplan['notizen'];
        $istAktiv = isset($data['ist_aktiv']) && $data['ist_aktiv'] === '1';
        
        $this->trainingsplanRepo->updateTrainingsplan($planId, [
            'plan_name' => $planName,
            'schwierigkeitsgrad' => $schwierigkeitsgrad,
            'trainingsart' => $trainingsart,
            'notizen' => $notizen,
            'ist_aktiv' => $istAktiv
        ]);
        
        // Übungen aktualisieren
        if (isset($data['uebungen']) && is_array($data['uebungen'])) {
            foreach ($data['uebungen'] as $uebungId => $uebungData) {
                // Prüfen, ob die Übung ersetzt werden soll
                if (isset($uebungData['ersetzen']) && $uebungData['ersetzen'] === '1' && !empty($uebungData['neue_uebung_id'])) {
                    // Übung ersetzen
                    $this->trainingsplanRepo->replaceUebung($uebungId, $uebungData['neue_uebung_id']);
                }
                
                // Reihenfolge aktualisieren
                if (isset($uebungData['reihenfolge'])) {
                    $this->trainingsplanRepo->updateUebungReihenfolge($uebungId, (int)$uebungData['reihenfolge']);
                }
                
                // Trainingstag aktualisieren
                if (isset($uebungData['trainingstag'])) {
                    $this->trainingsplanRepo->updateUebungTrainingstag($uebungId, $uebungData['trainingstag']);
                }
                
                // Übungsdetails aktualisieren
                if (isset($uebungData['details']) && is_array($uebungData['details'])) {
                    foreach ($uebungData['details'] as $detailId => $detailData) {
                        $this->trainingsplanRepo->updateUebungDetail($detailId, [
                            'gewicht_kg' => $detailData['gewicht_kg'] ?? 0,
                            'gewicht_prozent' => $detailData['gewicht_prozent'] ?? null,
                            'wiederholungen' => $detailData['wiederholungen'] ?? 0,
                            'pause_sekunden' => $detailData['pause_sekunden'] ?? 60,
                            'notizen' => $detailData['notizen'] ?? null
                        ]);
                    }
                }
            }
        }
        
        // Neue Übungen hinzufügen
        if (isset($data['neue_uebungen']) && is_array($data['neue_uebungen'])) {
            foreach ($data['neue_uebungen'] as $neueUebungData) {
                if (!empty($neueUebungData['uebung_id']) && !empty($neueUebungData['trainingstag'])) {
                    $uebungId = (int)$neueUebungData['uebung_id'];
                    $trainingstag = $neueUebungData['trainingstag'];
                    
                    // Höchste Reihenfolge für diesen Tag ermitteln
                    $maxReihenfolge = $this->trainingsplanRepo->findMaxReihenfolgeForTag($planId, $trainingstag);
                    $reihenfolge = $maxReihenfolge + 1;
                    
                    // Neue Übung zum Trainingsplan hinzufügen
                    $trainingsplanUebungId = $this->trainingsplanRepo->addUebungToTrainingsplan([
                        'trainingsplan_id' => $planId,
                        'uebung_id' => $uebungId,
                        'reihenfolge' => $reihenfolge,
                        'trainingstag' => $trainingstag
                    ]);
                    
                    // Standardwerte für Sätze und Wiederholungen basierend auf Trainingsart
                    $saetze = 3; // Standard
                    $wiederholungen = 12; // Standard
                    $pause = 60; // Standard in Sekunden
                    $gewichtProzent = 60; // Standard
                    
                    switch ($trainingsart) {
                        case 'Kraftausdauer':
                            $saetze = 3;
                            $wiederholungen = 15;
                            $pause = 45;
                            $gewichtProzent = 40;
                            break;
                        case 'Muskelaufbau':
                            $saetze = 4;
                            $wiederholungen = 10;
                            $pause = 90;
                            $gewichtProzent = 70;
                            break;
                        case 'IK Training':
                            $saetze = 5;
                            $wiederholungen = 5;
                            $pause = 180;
                            $gewichtProzent = 85;
                            break;
                    }
                    
                    // Maximalkraft-Test für diese Übung finden, falls vorhanden
                    $gewicht = 0;
                    $uebung = $this->uebungRepo->findById($uebungId);
                    if ($uebung) {
                        $maximalkraftTest = $this->maximalkraftRepo->findLatestTestForExercise($trainingsplan['mitglied_id'], $uebung['name']);
                        if ($maximalkraftTest) {
                            $gewicht = ($maximalkraftTest['berechnetes_1rm'] * $gewichtProzent) / 100;
                            $gewicht = round($gewicht * 2) / 2; // Auf 0.5 runden
                        }
                    }
                    
                    // Sätze zum Trainingsplan hinzufügen
                    for ($satz = 1; $satz <= $saetze; $satz++) {
                        $this->trainingsplanRepo->addUebungDetails([
                            'trainingsplan_uebung_id' => $trainingsplanUebungId,
                            'satz_nummer' => $satz,
                            'gewicht_kg' => $gewicht,
                            'gewicht_prozent' => $gewichtProzent,
                            'wiederholungen' => $wiederholungen,
                            'pause_sekunden' => $pause
                        ]);
                    }
                }
            }
        }
        
        // Übungen entfernen
        if (isset($data['entfernen_uebungen']) && is_array($data['entfernen_uebungen'])) {
            foreach ($data['entfernen_uebungen'] as $uebungId) {
                $this->trainingsplanRepo->removeUebungFromTrainingsplan($uebungId);
            }
        }
        
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response
            ->withHeader('Location', $routeParser->urlFor('trainingsplan-anzeigen', ['plan_id' => $planId]))
            ->withStatus(302);
    }
}