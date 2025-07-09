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

    // Boolean-Felder explizit konvertieren, um sicherzustellen, dass sie als echte Booleans in Twig ankommen
    $trainingsplan['periodisierung'] = (bool)($trainingsplan['periodisierung'] ?? false);
    $trainingsplan['spezielle_techniken'] = (bool)($trainingsplan['spezielle_techniken'] ?? false);
    $trainingsplan['aufwaermphase'] = (bool)($trainingsplan['aufwaermphase'] ?? true);
    $trainingsplan['progression'] = (bool)($trainingsplan['progression'] ?? true);
    $trainingsplan['kardio_integration'] = (bool)($trainingsplan['kardio_integration'] ?? false);
    $trainingsplan['ist_aktiv'] = (bool)($trainingsplan['ist_aktiv'] ?? false);

    // Mitgliedsinformationen abrufen
    $mitglied = $this->mitgliedRepo->findByIdWithInfo($trainingsplan['mitglied_id']);
    if (!$mitglied) {
        return $response->withStatus(404);
    }

    // Übungen des Trainingsplans abrufen
    $uebungen = $this->trainingsplanRepo->findUebungenByPlanId($planId);

    // Wenn keine Übungen gefunden wurden, versuche Beispielübungen hinzuzufügen
    if (empty($uebungen)) {
        $this->trainingsplanRepo->addExampleUebungenToTrainingsplan($planId);
        // Nach dem Hinzufügen erneut abrufen
        $uebungen = $this->trainingsplanRepo->findUebungenByPlanId($planId);
    }

    // Debug-Information für leere Übungen
    if (empty($uebungen)) {
        // Wenn keine Übungen gefunden wurden, fügen wir eine Nachricht hinzu
        $trainingsplan['debug_info'] = "Keine Übungen für diesen Trainingsplan gefunden. Bitte fügen Sie Übungen hinzu.";
    }

    // Übungen nach Trainingstagen gruppieren
    $uebungenNachTag = [];
    foreach ($uebungen as $uebung) {
        $tag = $uebung['trainingstag'];
        if (!isset($uebungenNachTag[$tag])) {
            $uebungenNachTag[$tag] = [];
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

    // Ernährungshinweise basierend auf Trainingsziel generieren, falls nicht vorhanden
    if (empty($trainingsplan['ernaehrungshinweise'])) {
        $ernaehrungshinweise = $this->generateErnaehrungshinweise($trainingsplan['trainingsart'], $trainingsplan['spezifisches_ziel'] ?? null);
        // Hinweis: Wir aktualisieren hier nicht die Datenbank, sondern zeigen nur die generierten Hinweise an
        $trainingsplan['ernaehrungshinweise'] = $ernaehrungshinweise;
    }

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
    
    /**
    * Generiert Ernährungshinweise basierend auf Trainingsart und spezifischem Ziel
    */
    private function generateErnaehrungshinweise(string $trainingsart, ?string $spezifischesZiel): string
    {
        $hinweise = "Allgemeine Ernährungsempfehlungen:\n";
        $hinweise .= "- Ausreichend Protein für Muskelaufbau und -erhalt (1,6-2,0g pro kg Körpergewicht)\n";
        $hinweise .= "- Komplexe Kohlenhydrate für Energie (3-5g pro kg Körpergewicht)\n";
        $hinweise .= "- Gesunde Fette für Hormonhaushalt (0,5-1g pro kg Körpergewicht)\n";
        $hinweise .= "- Mindestens 2-3 Liter Wasser täglich\n\n";
        
        // Spezifische Hinweise je nach Trainingsart
        switch ($trainingsart) {
            case 'Kraftausdauer':
                $hinweise .= "Spezifische Empfehlungen für Kraftausdauer:\n";
                $hinweise .= "- Moderate Kohlenhydratzufuhr vor dem Training\n";
                $hinweise .= "- Proteinreiche Mahlzeit innerhalb von 30 Minuten nach dem Training\n";
                $hinweise .= "- Elektrolyte bei längeren Trainingseinheiten ergänzen\n";
                break;
            
            case 'Muskelaufbau':
                $hinweise .= "Spezifische Empfehlungen für Muskelaufbau:\n";
                $hinweise .= "- Kalorienüberschuss von 300-500 kcal täglich\n";
                $hinweise .= "- Erhöhte Proteinzufuhr (bis zu 2,2g pro kg Körpergewicht)\n";
                $hinweise .= "- Verteilung der Proteinzufuhr auf 4-6 Mahlzeiten über den Tag\n";
                break;
            
            case 'IK Training':
                $hinweise .= "Spezifische Empfehlungen für Intensitäts-/Krafttraining:\n";
                $hinweise .= "- Ausreichend Kohlenhydrate vor intensiven Trainingseinheiten\n";
                $hinweise .= "- Kreatin-Supplementierung kann die Kraftleistung verbessern\n";
                $hinweise .= "- Fokus auf Erholung und Regeneration zwischen den Trainingseinheiten\n";
                break;
        }
        
        // Zusätzliche Hinweise je nach spezifischem Ziel
        if ($spezifischesZiel) {
            $hinweise .= "\nEmpfehlungen für Ihr spezifisches Ziel ($spezifischesZiel):\n";
            
            switch ($spezifischesZiel) {
                case 'Definitionsphase':
                    $hinweise .= "- Leichtes Kaloriendefizit (300-500 kcal täglich)\n";
                    $hinweise .= "- Erhöhte Proteinzufuhr zum Muskelerhalt\n";
                    $hinweise .= "- Reduzierte Kohlenhydratzufuhr, besonders abends\n";
                    break;
                
                case 'Maximalkraft':
                    $hinweise .= "- Ausreichend Kalorien für optimale Leistung\n";
                    $hinweise .= "- Kreatin und Beta-Alanin können die Kraftleistung unterstützen\n";
                    $hinweise .= "- Fokus auf Regeneration und qualitativ hochwertige Nahrungsmittel\n";
                    break;
                
                case 'Athletik':
                    $hinweise .= "- Ausgewogene Makronährstoffverteilung\n";
                    $hinweise .= "- Timing der Nährstoffzufuhr um Trainingseinheiten optimieren\n";
                    $hinweise .= "- Antioxidantien zur Unterstützung der Erholung\n";
                    break;
                
                case 'Rehabilitation':
                    $hinweise .= "- Entzündungshemmende Lebensmittel bevorzugen\n";
                    $hinweise .= "- Ausreichend Protein für Geweberegeneration\n";
                    $hinweise .= "- Omega-3-Fettsäuren zur Unterstützung der Heilung\n";
                    break;
            }
        }
        
        return $hinweise;
    }
}