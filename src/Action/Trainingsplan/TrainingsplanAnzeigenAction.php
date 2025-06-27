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

final class TrainingsplanAnzeigenAction
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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
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
        $uebungen = $this->trainingsplanRepo->findUebungenByPlanId($planId);
        
        // Übungen nach Trainingstagen gruppieren
        $uebungenNachTag = [];
        foreach ($uebungen as $uebung) {
            $tag = $uebung['trainingstag'];
            if (!isset($uebungenNachTag[$tag])) {
                $uebungenNachTag[$tag] = [];
            }
            
            // Details für jede Übung abrufen (Sätze, Wiederholungen, Gewichte)
            $uebung['details'] = $this->trainingsplanRepo->findUebungDetailsByUebungId($uebung['trainingsplan_uebung_id']);
            
            // Maximalkraft-Test für diese Übung finden, falls vorhanden
            $maximalkraftTest = $this->maximalkraftRepo->findLatestTestForExercise($mitglied['mitglied_id'], $uebung['name']);
            if ($maximalkraftTest) {
                $uebung['maximalkraft_test'] = $maximalkraftTest;
            }
            
            $uebungenNachTag[$tag][] = $uebung;
        }
        
        // Sortieren der Wochentage in richtiger Reihenfolge
        $sortierteTage = [
            'Montag' => $uebungenNachTag['Montag'] ?? [],
            'Dienstag' => $uebungenNachTag['Dienstag'] ?? [],
            'Mittwoch' => $uebungenNachTag['Mittwoch'] ?? [],
            'Donnerstag' => $uebungenNachTag['Donnerstag'] ?? [],
            'Freitag' => $uebungenNachTag['Freitag'] ?? [],
            'Samstag' => $uebungenNachTag['Samstag'] ?? [],
            'Sonntag' => $uebungenNachTag['Sonntag'] ?? []
        ];
        
        // Leere Tage entfernen
        $sortierteTage = array_filter($sortierteTage, function($tag) {
            return !empty($tag);
        });
        
        // Maximalkraft-Tests des Mitglieds abrufen für die Anzeige
        $maximalkraftTests = $this->maximalkraftRepo->findLatestTestsPerExercise($mitglied['mitglied_id']);
        
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        
        $data = [
            'title' => 'Trainingsplan: ' . $trainingsplan['plan_name'],
            'trainingsplan' => $trainingsplan,
            'mitglied' => $mitglied,
            'uebungen' => $uebungen,
            'uebungenNachTag' => $sortierteTage,
            'maximalkraftTests' => $maximalkraftTests,
            'editUrl' => $routeParser->urlFor('trainingsplan-bearbeiten', ['plan_id' => $planId]),
            'deleteUrl' => $routeParser->urlFor('trainingsplan-loeschen', ['plan_id' => $planId]),
            'backUrl' => $routeParser->urlFor('mitglied-trainingsplaene', ['id' => $mitglied['mitglied_id']])
        ];
        
        return $this->view->render($response, 'trainingsplan/anzeigen.twig', $data);
    }
}