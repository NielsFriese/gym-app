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
        
        // Alle u00dcbungen des Trainingsplans abrufen
        $planUebungen = $this->trainingsplanRepo->findUebungenByPlanId($planId);
        
        // u00dcbungen nach Trainingstagen gruppieren
        $uebungenNachTag = [];
        foreach ($planUebungen as $uebung) {
            $tag = $uebung['trainingstag'];
            if (!isset($uebungenNachTag[$tag])) {
                $uebungenNachTag[$tag] = [];
            }
            
            // Details fu00fcr jede u00dcbung abrufen (Su00e4tze, Wiederholungen, Gewichte)
            $uebung['details'] = $this->trainingsplanRepo->findUebungDetailsByUebungId($uebung['trainingsplan_uebung_id']);
            $uebungenNachTag[$tag][] = $uebung;
        }
        
        // Alle verfu00fcgbaren u00dcbungen abrufen fu00fcr die Auswahl von Ersatzu00fcbungen
        $alleUebungen = $this->uebungRepo->findAll();
        
        // Gruppieren der u00dcbungen nach Muskelgruppen fu00fcr bessere u00dcbersicht
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
        
        // Ku00f6rperbereiche abrufen
        $koerperbereiche = $this->uebungRepo->findAllKoerperbereiche();
        
        // u00dcbungstypen abrufen
        $uebungstypen = $this->uebungRepo->findAllUebungstypen();
        
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        
        $data = [
            'title' => 'Trainingsplan bearbeiten: ' . $trainingsplan['plan_name'],
            'trainingsplan' => $trainingsplan,
            'mitglied' => $mitglied,
            'uebungenNachTag' => $uebungenNachTag,
            'alleUebungen' => $alleUebungen,
            'uebungenNachMuskelgruppe' => $uebungenNachMuskelgruppe,
            'koerperbereiche' => $koerperbereiche,
            'uebungstypen' => $uebungstypen,
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
        
        // Debug-Ausgabe fu00fcr die Formulardaten
        error_log('Formulardaten: ' . print_r($data, true));
        
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
        
        // Neue Parameter
        $splitTyp = $data['split_typ'] ?? $trainingsplan['split_typ'];
        $trainingsdauer = (int)($data['trainingsdauer'] ?? $trainingsplan['trainingsdauer']);
        $trainingsumgebung = $data['trainingsumgebung'] ?? $trainingsplan['trainingsumgebung'];
        $trainingsfrequenz = (int)($data['trainingsfrequenz'] ?? $trainingsplan['trainingsfrequenz']);
        $spezifischesZiel = $data['spezifisches_ziel'] ?? $trainingsplan['spezifisches_ziel'];
        $koerperlicheEinschraenkungen = $data['koerperliche_einschraenkungen'] ?? $trainingsplan['koerperliche_einschraenkungen'];
        $erholungszeit = $data['erholungszeit'] ?? $trainingsplan['erholungszeit'];
        $periodisierung = isset($data['periodisierung']) && $data['periodisierung'] === '1';
        $spezielleTechniken = isset($data['spezielle_techniken']) && $data['spezielle_techniken'] === '1';
        $aufwaermphase = isset($data['aufwaermphase']) && $data['aufwaermphase'] === '1';
        $progression = isset($data['progression']) && $data['progression'] === '1';
        $kardioIntegration = isset($data['kardio_integration']) && $data['kardio_integration'] === '1';
        $ernaehrungshinweise = $data['ernaehrungshinweise'] ?? $trainingsplan['ernaehrungshinweise'];
        
        $this->trainingsplanRepo->updateTrainingsplan($planId, [
            'plan_name' => $planName,
            'schwierigkeitsgrad' => $schwierigkeitsgrad,
            'trainingsart' => $trainingsart,
            'split_typ' => $splitTyp,
            'trainingsdauer' => $trainingsdauer,
            'trainingsumgebung' => $trainingsumgebung,
            'trainingsfrequenz' => $trainingsfrequenz,
            'spezifisches_ziel' => $spezifischesZiel,
            'koerperliche_einschraenkungen' => $koerperlicheEinschraenkungen,
            'erholungszeit' => $erholungszeit,
            'periodisierung' => $periodisierung,
            'spezielle_techniken' => $spezielleTechniken,
            'aufwaermphase' => $aufwaermphase,
            'progression' => $progression,
            'kardio_integration' => $kardioIntegration,
            'ernaehrungshinweise' => $ernaehrungshinweise,
            'notizen' => $notizen,
            'ist_aktiv' => $istAktiv
        ]);
        
        // u00dcbungen aktualisieren
        if (isset($data['uebungen']) && is_array($data['uebungen'])) {
            foreach ($data['uebungen'] as $uebungId => $uebungData) {
                // Pru00fcfen, ob die u00dcbung ersetzt werden soll
                if (isset($uebungData['ersetzen']) && $uebungData['ersetzen'] === '1' && !empty($uebungData['neue_uebung_id'])) {
                    // u00dcbung ersetzen
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
                
                // u00dcbungsdetails aktualisieren
                if (isset($uebungData['details']) && is_array($uebungData['details'])) {
                    foreach ($uebungData['details'] as $detailId => $detailData) {
                        $this->trainingsplanRepo->updateUebungDetail($detailId, [
                            'gewicht_kg' => $detailData['gewicht_kg'] ?? 0,
                            'gewicht_prozent' => $detailData['gewicht_prozent'] ?? null,
                            'wiederholungen' => $detailData['wiederholungen'] ?? 0,
                            'pause_sekunden' => $detailData['pause_sekunden'] ?? 60,
                            'technik' => $detailData['technik'] ?? 'Normal',
                            'notizen' => $detailData['notizen'] ?? null
                        ]);
                    }
                }
            }
        }
        
        // Neue u00dcbungen hinzufu00fcgen
        error_log('Neue u00dcbungen: ' . (isset($data['neue_uebungen']) ? 'Ja' : 'Nein'));
        if (isset($data['neue_uebungen']) && is_array($data['neue_uebungen'])) {
            error_log('Anzahl neuer u00dcbungen: ' . count($data['neue_uebungen']));
            foreach ($data['neue_uebungen'] as $index => $neueUebungData) {
                error_log('Verarbeite neue u00dcbung ' . $index . ': ' . print_r($neueUebungData, true));
                if (!empty($neueUebungData['uebung_id']) && !empty($neueUebungData['trainingstag'])) {
                    $uebungId = (int)$neueUebungData['uebung_id'];
                    $trainingstag = $neueUebungData['trainingstag'];
                    
                    error_log('Fu00fcge neue u00dcbung hinzu: ID=' . $uebungId . ', Tag=' . $trainingstag);
                    
                    // Hu00f6chste Reihenfolge fu00fcr diesen Tag ermitteln
                    $maxReihenfolge = $this->trainingsplanRepo->findMaxReihenfolgeForTag($planId, $trainingstag);
                    $reihenfolge = $maxReihenfolge + 1;
                    
                    error_log('Neue Reihenfolge: ' . $reihenfolge . ' (nach Max: ' . $maxReihenfolge . ')');
                    
                    // Neue u00dcbung zum Trainingsplan hinzufu00fcgen
                    $trainingsplanUebungId = $this->trainingsplanRepo->addUebungToTrainingsplan([
                        'trainingsplan_id' => $planId,
                        'uebung_id' => $uebungId,
                        'reihenfolge' => $reihenfolge,
                        'trainingstag' => $trainingstag
                    ]);
                    
                    error_log('Neue u00dcbung hinzugefu00fcgt mit ID: ' . $trainingsplanUebungId);
                    
                    // Standardwerte fu00fcr Su00e4tze und Wiederholungen basierend auf Trainingsart
                    $saetze = 3; // Standard
                    $wiederholungen = 12; // Standard
                    $pause = 60; // Standard in Sekunden
                    $gewichtProzent = 60; // Standard
                    
                    switch ($trainingsart) {
                        case 'Kraftausdauer':
                            $saetze = rand(3, 4);
                            $wiederholungen = rand(15, 22);
                            $pause = rand(30, 60);
                            $gewichtProzent = rand(30, 50);
                            break;
                        case 'Muskelaufbau':
                            $saetze = rand(2, 3);
                            $wiederholungen = rand(6, 10);
                            $pause = rand(90, 120);
                            $gewichtProzent = rand(65, 80);
                            break;
                        case 'IK Training':
                            $saetze = rand(1, 2);
                            $wiederholungen = rand(1, 4);
                            $pause = rand(180, 300);
                            $gewichtProzent = rand(85, 95);
                            break;
                    }
                    
                    // Pause basierend auf Erholungszeit anpassen
                    switch ($erholungszeit) {
                        case 'Kurz':
                            $pause = rand(30, 60);
                            break;
                        case 'Mittel':
                            $pause = rand(90, 120);
                            break;
                        case 'Lang':
                            $pause = rand(120, 300);
                            break;
                    }
                    
                    // Maximalkraft-Test fu00fcr diese u00dcbung finden, falls vorhanden
                    $gewicht = 0;
                    $uebung = $this->uebungRepo->findById($uebungId);
                    if ($uebung) {
                        $maximalkraftTest = $this->maximalkraftRepo->findLatestTestForExercise($trainingsplan['mitglied_id'], $uebung['name']);
                        if ($maximalkraftTest) {
                            $gewicht = ($maximalkraftTest['berechnetes_1rm'] * $gewichtProzent) / 100;
                            $gewicht = round($gewicht * 2) / 2; // Auf 0.5 runden
                        }
                    }
                    
                    // Technik bestimmen (Normal, Supersatz, Dropset)
                    $technik = 'Normal';
                    if ($spezielleTechniken) {
                        // 20% Chance auf spezielle Technik
                        if (rand(1, 5) == 1) {
                            $technik = (rand(0, 1) == 0) ? 'Supersatz' : 'Dropset';
                        }
                    }
                    
                    error_log('Fu00fcge ' . $saetze . ' Su00e4tze hinzu');
                    
                    // Su00e4tze zum Trainingsplan hinzufu00fcgen
                    for ($satz = 1; $satz <= $saetze; $satz++) {
                        $detailId = $this->trainingsplanRepo->addUebungDetails([
                            'trainingsplan_uebung_id' => $trainingsplanUebungId,
                            'satz_nummer' => $satz,
                            'gewicht_kg' => $gewicht,
                            'gewicht_prozent' => $gewichtProzent,
                            'wiederholungen' => $wiederholungen,
                            'pause_sekunden' => $pause,
                            'technik' => $technik,
                            'notizen' => ''
                        ]);
                        error_log('Satz ' . $satz . ' hinzugefu00fcgt mit Detail-ID: ' . $detailId);
                    }
                }
            }
        }
        
        // u00dcbungen entfernen
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