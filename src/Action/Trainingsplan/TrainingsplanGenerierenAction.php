<?php

namespace App\Action\Trainingsplan;

use App\Domain\Mitglied\MitgliedRepository;
use App\Domain\Trainingsplan\TrainingsplanRepository;
use App\Domain\Uebungen\UebungRepository;
use App\Domain\Maximalkraft\MaximalkraftTestRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class TrainingsplanGenerierenAction
{
    private MitgliedRepository $mitgliedRepo;
    private TrainingsplanRepository $trainingsplanRepo;
    private UebungRepository $uebungRepo;
    private MaximalkraftTestRepository $maximalkraftRepo;
    private Twig $view;

    public function __construct(
    MitgliedRepository $mitgliedRepo,
    TrainingsplanRepository $trainingsplanRepo,
    UebungRepository $uebungRepo,
    MaximalkraftTestRepository $maximalkraftRepo,
    Twig $twig
    ) {
    $this->mitgliedRepo = $mitgliedRepo;
    $this->trainingsplanRepo = $trainingsplanRepo;
    $this->uebungRepo = $uebungRepo;
    $this->maximalkraftRepo = $maximalkraftRepo;
    $this->view = $twig;
    }

    public function showForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
    $mitgliedId = (int)$args['id'];
    
    // Prüfen, ob das Mitglied existiert
    $mitglied = $this->mitgliedRepo->findByIdWithInfo($mitgliedId);
    if (!$mitglied) {
        return $response->withStatus(404);
    }
    
    // Maximalkraft-Tests des Mitglieds abrufen
    $maximalkraftTests = $this->maximalkraftRepo->findLatestTestsPerExercise($mitgliedId);
    
    // Alle verfügbaren Übungen abrufen
    $uebungen = $this->uebungRepo->findAll();
    
    // Gruppieren der Übungen nach Muskelgruppen für bessere Übersicht
    $uebungenNachMuskelgruppe = [];
    foreach ($uebungen as $uebung) {
        $muskelgruppe = $uebung['muskelgruppe'];
        if (!isset($uebungenNachMuskelgruppe[$muskelgruppe])) {
            $uebungenNachMuskelgruppe[$muskelgruppe] = [];
        }
        $uebungenNachMuskelgruppe[$muskelgruppe][] = $uebung;
    }
    
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    
    $data = [
        'title' => 'Trainingsplan generieren für ' . $mitglied['vorname'] . ' ' . $mitglied['nachname'],
        'mitglied' => $mitglied,
        'maximalkraftTests' => $maximalkraftTests,
        'uebungen' => $uebungen,
        'uebungenNachMuskelgruppe' => $uebungenNachMuskelgruppe,
        'submitUrl' => $routeParser->urlFor('trainingsplan-generieren-submit', ['id' => $mitgliedId]),
        'backUrl' => $routeParser->urlFor('mitglied-details', ['id' => $mitgliedId])
    ];
    
    return $this->view->render($response, 'trainingsplan/generieren-form.twig', $data);
    }

    public function handleSubmit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
    $mitgliedId = (int)$args['id'];
    $data = $request->getParsedBody();
    
    // Prüfen, ob das Mitglied existiert
    $mitglied = $this->mitgliedRepo->findById($mitgliedId);
    if (!$mitglied) {
        return $response->withStatus(404);
    }
    
    // Validierung der Eingabedaten
    $planName = $data['plan_name'] ?? 'Trainingsplan';
    $schwierigkeitsgrad = $data['schwierigkeitsgrad'] ?? 'Anfänger';
    $trainingsart = $data['trainingsart'] ?? 'Kraftausdauer';
    $notizen = $data['notizen'] ?? '';
    $useMaximalkraft = isset($data['use_maximalkraft']) && $data['use_maximalkraft'] === '1';
    $manuelleGewichte = isset($data['manuelle_gewichte']) && $data['manuelle_gewichte'] === '1';
    
    // Trainingsplan in der Datenbank erstellen
    $trainingsplanId = $this->trainingsplanRepo->createTrainingsplan([
        'mitglied_id' => $mitgliedId,
        'plan_name' => $planName,
        'schwierigkeitsgrad' => $schwierigkeitsgrad,
        'trainingsart' => $trainingsart,
        'notizen' => $notizen,
        'ist_aktiv' => true
    ]);
    
    // Übungen basierend auf Schwierigkeitsgrad und Trainingsart auswählen
    $passendUebungen = $this->uebungRepo->findByTrainingsartAndSchwierigkeit($trainingsart, $schwierigkeitsgrad);
    
    // Wenn manuelle Übungsauswahl erfolgt ist, diese berücksichtigen
    if (isset($data['selected_uebungen']) && is_array($data['selected_uebungen']) && count($data['selected_uebungen']) > 0) {
        $selectedUebungen = $data['selected_uebungen'];
        // Filtere die passenden Übungen nach den ausgewählten
        $ausgewaehlteUebungen = array_filter($passendUebungen, function($uebung) use ($selectedUebungen) {
            return in_array($uebung['uebung_id'], $selectedUebungen);
        });
        
        // Nur wenn tatsächlich Übungen ausgewählt wurden, ersetze die automatisch ausgewählten
        if (count($ausgewaehlteUebungen) > 0) {
            $passendUebungen = $ausgewaehlteUebungen;
        }
    }
    
    // Sicherstellen, dass mindestens 7 Übungen im Trainingsplan sind
    if (count($passendUebungen) < 7) {
        // Wenn weniger als 7 Übungen ausgewählt wurden, füge weitere hinzu
        $alleUebungen = $this->uebungRepo->findAll();
        $zusaetzlicheUebungen = array_filter($alleUebungen, function($uebung) use ($passendUebungen) {
            foreach ($passendUebungen as $passendUebung) {
                if ($passendUebung['uebung_id'] == $uebung['uebung_id']) {
                    return false;
                }
            }
            return true;
        });
        
        // Füge zusätzliche Übungen hinzu, bis wir mindestens 7 haben
        $i = 0;
        while (count($passendUebungen) < 7 && $i < count($zusaetzlicheUebungen)) {
            $passendUebungen[] = $zusaetzlicheUebungen[$i];
            $i++;
        }
    }
    
    // Übungen auf die Wochentage verteilen
    $wochentage = ['Montag', 'Mittwoch', 'Freitag']; // Standard-Trainingstage
    if (isset($data['trainingstage']) && is_array($data['trainingstage'])) {
        $wochentage = $data['trainingstage'];
    }
    
    $uebungenProTag = ceil(count($passendUebungen) / count($wochentage));
    $uebungenNachTag = [];
    
    // Übungen gleichmäßig auf die Trainingstage verteilen
    $uebungIndex = 0;
    foreach ($wochentage as $tag) {
        $uebungenNachTag[$tag] = [];
        for ($i = 0; $i < $uebungenProTag && $uebungIndex < count($passendUebungen); $i++) {
            $uebungenNachTag[$tag][] = $passendUebungen[$uebungIndex];
            $uebungIndex++;
        }
    }
    
    // Übungen zum Trainingsplan hinzufügen
    foreach ($uebungenNachTag as $tag => $tagUebungen) {
        $reihenfolge = 1;
        foreach ($tagUebungen as $uebung) {
            $trainingsplanUebungId = $this->trainingsplanRepo->addUebungToTrainingsplan([
                'trainingsplan_id' => $trainingsplanId,
                'uebung_id' => $uebung['uebung_id'],
                'reihenfolge' => $reihenfolge,
                'trainingstag' => $tag
            ]);
            
            // Sätze und Wiederholungen basierend auf Trainingsart festlegen
            $saetze = 3; // Standard
            $wiederholungen = 12; // Standard
            $pause = 60; // Standard in Sekunden
            
            switch ($trainingsart) {
                case 'Kraftausdauer':
                    $saetze = 3;
                    $wiederholungen = 15;
                    $pause = 45;
                    $gewichtProzent = 40; // 40% des 1RM
                    break;
                case 'Muskelaufbau':
                    $saetze = 4;
                    $wiederholungen = 10;
                    $pause = 90;
                    $gewichtProzent = 70; // 70% des 1RM
                    break;
                case 'IK Training':
                    $saetze = 5;
                    $wiederholungen = 5;
                    $pause = 180;
                    $gewichtProzent = 85; // 85% des 1RM
                    break;
            }
            
            // Gewicht bestimmen - entweder manuell oder aus Maximalkraft-Test
            $gewicht = 0;
            
            // Wenn manuelle Gewichte verwendet werden sollen
            if ($manuelleGewichte && isset($data['gewicht'][$uebung['uebung_id']])) {
                $gewicht = (float)$data['gewicht'][$uebung['uebung_id']];
            } 
            // Sonst Maximalkraft-Test verwenden, falls vorhanden und gewünscht
            elseif ($useMaximalkraft) {
                $maximalkraftTest = $this->maximalkraftRepo->findLatestTestForExercise($mitgliedId, $uebung['name']);
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
            
            $reihenfolge++;
        }
    }
    
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('trainingsplan-anzeigen', ['plan_id' => $trainingsplanId]))
        ->withStatus(302);
    }
}