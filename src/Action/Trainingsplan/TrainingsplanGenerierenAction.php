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
    
    // Körperbereiche abrufen
    $koerperbereiche = $this->uebungRepo->findAllKoerperbereiche();
    
    // Übungstypen abrufen
    $uebungstypen = $this->uebungRepo->findAllUebungstypen();
    
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    
    $data = [
    'title' => 'Trainingsplan generieren für ' . $mitglied['vorname'] . ' ' . $mitglied['nachname'],
    'mitglied' => $mitglied,
    'maximalkraftTests' => $maximalkraftTests,
    'uebungen' => $uebungen,
    'uebungenNachMuskelgruppe' => $uebungenNachMuskelgruppe,
    'koerperbereiche' => $koerperbereiche,
    'uebungstypen' => $uebungstypen,
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
    
    // Neue Parameter
    $splitTyp = $data['split_typ'] ?? 'Ganzkörper';
    $trainingsdauer = (int)($data['trainingsdauer'] ?? 60);
    $trainingsumgebung = $data['trainingsumgebung'] ?? 'Fitnessstudio';
    $trainingsfrequenz = (int)($data['trainingsfrequenz'] ?? 3);
    $spezifischesZiel = $data['spezifisches_ziel'] ?? null;
    $koerperlicheEinschraenkungen = $data['koerperliche_einschraenkungen'] ?? null;
    $erholungszeit = $data['erholungszeit'] ?? 'Mittel';
    $periodisierung = isset($data['periodisierung']) && $data['periodisierung'] === '1';
    $spezielleTechniken = isset($data['spezielle_techniken']) && $data['spezielle_techniken'] === '1';
    $aufwaermphase = isset($data['aufwaermphase']) && $data['aufwaermphase'] === '1';
    $progression = isset($data['progression']) && $data['progression'] === '1';
    $kardioIntegration = isset($data['kardio_integration']) && $data['kardio_integration'] === '1';
    $ernaehrungshinweise = $data['ernaehrungshinweise'] ?? null;
    
    // Trainingsplan in der Datenbank erstellen
    $trainingsplanId = $this->trainingsplanRepo->createTrainingsplan([
    'mitglied_id' => $mitgliedId,
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
    'ist_aktiv' => true
    ]);
    
    // Übungstypen basierend auf Schwierigkeitsgrad und Trainingsumgebung bestimmen
    $erlaubteUebungstypen = $this->getErlaubteUebungstypen($schwierigkeitsgrad, $trainingsumgebung);
    
    // Übungen basierend auf Trainingsart, Schwierigkeitsgrad und Übungstyp auswählen
    $passendUebungen = $this->uebungRepo->findByTrainingsartSchwierigkeitAndUebungstyp(
    $trainingsart, 
    $schwierigkeitsgrad, 
    $erlaubteUebungstypen
    );
    
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
    
    // Körperliche Einschränkungen berücksichtigen
    if (!empty($koerperlicheEinschraenkungen)) {
    $passendUebungen = $this->filterUebungenNachEinschraenkungen($passendUebungen, $koerperlicheEinschraenkungen);
    }
    
    // Übungen nach Körperbereichen und Muskelgruppen gruppieren für bessere Verteilung
    $uebungenNachKoerperbereich = $this->gruppiereUebungenNachKoerperbereich($passendUebungen);
    $uebungenNachMuskelgruppe = $this->gruppiereUebungenNachMuskelgruppe($passendUebungen);
    
    // Sicherstellen, dass mindestens 7 Übungen im Trainingsplan sind
    $minUebungen = 7;
    $gesamtUebungen = count($passendUebungen);
    
    if ($gesamtUebungen < $minUebungen) {
    // Wenn weniger als 7 Übungen ausgewählt wurden, füge weitere hinzu
    $passendUebungen = $this->ergaenzeUebungen($passendUebungen, $erlaubteUebungstypen, $minUebungen, $koerperlicheEinschraenkungen);
    
    // Aktualisiere die Gruppierungen
    $uebungenNachKoerperbereich = $this->gruppiereUebungenNachKoerperbereich($passendUebungen);
    $uebungenNachMuskelgruppe = $this->gruppiereUebungenNachMuskelgruppe($passendUebungen);
    }
    
    // Trainingstage basierend auf Split-Typ und Trainingsfrequenz bestimmen
    $wochentage = ['Montag', 'Mittwoch', 'Freitag']; // Standard-Trainingstage
    if (isset($data['trainingstage']) && is_array($data['trainingstage'])) {
    $wochentage = $data['trainingstage'];
    }
    
    // Übungen auf die Wochentage verteilen basierend auf Split-Typ
    $uebungenNachTag = $this->verteileUebungenAufTage(
    $passendUebungen, 
    $uebungenNachKoerperbereich, 
    $uebungenNachMuskelgruppe, 
    $wochentage, 
    $splitTyp
    );
    
    // Sätze, Wiederholungen und Pausen basierend auf Trainingsart festlegen
    $trainingsParameter = $this->bestimmeTrainingsParameter($trainingsart, $erholungszeit);
    
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
    
    // Gewicht bestimmen - entweder manuell oder aus Maximalkraft-Test
    $gewicht = $this->bestimmeGewicht(
    $uebung, 
    $mitgliedId, 
    $trainingsParameter['gewichtProzent'], 
    $useMaximalkraft, 
    $manuelleGewichte, 
    $data
    );
    
    // Technik bestimmen (Normal, Supersatz, Dropset)
    $technik = $this->bestimmeTechnik($spezielleTechniken);
    
    // Sätze zum Trainingsplan hinzufügen
    for ($satz = 1; $satz <= $trainingsParameter['saetze']; $satz++) {
    $this->trainingsplanRepo->addUebungDetails([
    'trainingsplan_uebung_id' => $trainingsplanUebungId,
    'satz_nummer' => $satz,
    'gewicht_kg' => $gewicht,
    'gewicht_prozent' => $trainingsParameter['gewichtProzent'],
    'wiederholungen' => $trainingsParameter['wiederholungen'],
    'pause_sekunden' => $trainingsParameter['pause'],
    'technik' => $technik
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
    
    /**
     * Bestimmt die erlaubten Übungstypen basierend auf Schwierigkeitsgrad und Trainingsumgebung
     */
    private function getErlaubteUebungstypen(string $schwierigkeitsgrad, string $trainingsumgebung): array
    {
        $erlaubteUebungstypen = [];
        
        // Basis-Übungstypen nach Schwierigkeitsgrad
        switch ($schwierigkeitsgrad) {
            case 'Anfänger':
                $erlaubteUebungstypen = ['Geführtes Kraftgerät'];
                break;
            case 'Fortgeschritten':
                $erlaubteUebungstypen = ['Geführtes Kraftgerät', 'Freihantel'];
                break;
            case 'Experte':
                $erlaubteUebungstypen = ['Geführtes Kraftgerät', 'Freihantel', 'Kabelzug', 'Eigengewicht', 'Sonstige'];
                break;
        }
        
        // Anpassung basierend auf Trainingsumgebung
        if ($trainingsumgebung === 'Heimtraining') {
            // Bei Heimtraining mehr Fokus auf Eigengewichtsübungen und weniger auf Geräte
            $erlaubteUebungstypen[] = 'Eigengewicht';
            
            // Entferne geführte Kraftgeräte, wenn es Heimtraining ist und der Nutzer kein Experte ist
            if ($schwierigkeitsgrad !== 'Experte') {
                $erlaubteUebungstypen = array_diff($erlaubteUebungstypen, ['Geführtes Kraftgerät']);
            }
        }
        
        // Entferne Duplikate
        return array_unique($erlaubteUebungstypen);
    }
    
    /**
     * Filtert Übungen basierend auf körperlichen Einschränkungen
     */
    private function filterUebungenNachEinschraenkungen(array $uebungen, string $einschraenkungen): array
    {
        $einschraenkungen = strtolower($einschraenkungen);
        
        // Definiere Schlüsselwörter für verschiedene Einschränkungen und die zu vermeidenden Muskelgruppen
        $einschraenkungsFilter = [
            'knie' => ['Beine', 'Quadrizeps', 'Oberschenkel'],
            'rücken' => ['Rücken', 'Lenden', 'Unterer Rücken'],
            'schulter' => ['Schulter', 'Deltoid'],
            'ellbogen' => ['Bizeps', 'Trizeps'],
            'handgelenk' => ['Unterarm', 'Bizeps', 'Trizeps'],
            'nacken' => ['Nacken', 'Trapezius'],
            'hüfte' => ['Hüfte', 'Gesäß', 'Beine']
        ];
        
        $zuVermeidendeMuskelgruppen = [];
        
        // Überprüfe, ob eine der Einschränkungen im Text vorkommt
        foreach ($einschraenkungsFilter as $schluesselwort => $muskelgruppen) {
            if (strpos($einschraenkungen, $schluesselwort) !== false) {
                $zuVermeidendeMuskelgruppen = array_merge($zuVermeidendeMuskelgruppen, $muskelgruppen);
            }
        }
        
        // Wenn keine spezifischen Einschränkungen gefunden wurden, gib alle Übungen zurück
        if (empty($zuVermeidendeMuskelgruppen)) {
            return $uebungen;
        }
        
        // Filtere Übungen, die die zu vermeidenden Muskelgruppen betreffen
        return array_filter($uebungen, function($uebung) use ($zuVermeidendeMuskelgruppen) {
            foreach ($zuVermeidendeMuskelgruppen as $muskelgruppe) {
                if (stripos($uebung['muskelgruppe'], $muskelgruppe) !== false) {
                    return false;
                }
            }
            return true;
        });
    }
    
    /**
     * Gruppiert Übungen nach Körperbereich
     */
    private function gruppiereUebungenNachKoerperbereich(array $uebungen): array
    {
        $uebungenNachKoerperbereich = [];
        foreach ($uebungen as $uebung) {
            $koerperbereich = $uebung['koerperbereich'] ?? 'Sonstige';
            if (!isset($uebungenNachKoerperbereich[$koerperbereich])) {
                $uebungenNachKoerperbereich[$koerperbereich] = [];
            }
            $uebungenNachKoerperbereich[$koerperbereich][] = $uebung;
        }
        return $uebungenNachKoerperbereich;
    }
    
    /**
     * Gruppiert Übungen nach Muskelgruppe
     */
    private function gruppiereUebungenNachMuskelgruppe(array $uebungen): array
    {
        $uebungenNachMuskelgruppe = [];
        foreach ($uebungen as $uebung) {
            $muskelgruppe = $uebung['muskelgruppe'] ?? 'Sonstige';
            if (!isset($uebungenNachMuskelgruppe[$muskelgruppe])) {
                $uebungenNachMuskelgruppe[$muskelgruppe] = [];
            }
            $uebungenNachMuskelgruppe[$muskelgruppe][] = $uebung;
        }
        return $uebungenNachMuskelgruppe;
    }
    
    /**
     * Ergänzt die Übungsliste, um die Mindestanzahl zu erreichen
     */
    private function ergaenzeUebungen(array $uebungen, array $erlaubteUebungstypen, int $minUebungen, ?string $einschraenkungen): array
    {
        // Wenn bereits genug Übungen vorhanden sind, nichts tun
        if (count($uebungen) >= $minUebungen) {
            return $uebungen;
        }
        
        // Alle Übungen abrufen
        $alleUebungen = $this->uebungRepo->findAll();
        
        // Bereits vorhandene Übungs-IDs sammeln
        $vorhandeneIds = array_map(function($uebung) {
            return $uebung['uebung_id'];
        }, $uebungen);
        
        // Zusätzliche Übungen filtern
        $zusaetzlicheUebungen = array_filter($alleUebungen, function($uebung) use ($vorhandeneIds, $erlaubteUebungstypen) {
            // Übung nicht hinzufügen, wenn sie bereits im Plan ist
            if (in_array($uebung['uebung_id'], $vorhandeneIds)) {
                return false;
            }
            
            // Übung nur hinzufügen, wenn der Übungstyp erlaubt ist
            return in_array($uebung['uebungstyp'], $erlaubteUebungstypen);
        });
        
        // Wenn körperliche Einschränkungen vorhanden sind, diese berücksichtigen
        if (!empty($einschraenkungen)) {
            $zusaetzlicheUebungen = $this->filterUebungenNachEinschraenkungen($zusaetzlicheUebungen, $einschraenkungen);
        }
        
        // Sortiere die zusätzlichen Übungen nach Muskelgruppen, um eine gute Verteilung zu gewährleisten
        usort($zusaetzlicheUebungen, function($a, $b) {
            return strcmp($a['muskelgruppe'], $b['muskelgruppe']);
        });
        
        // Füge zusätzliche Übungen hinzu, bis die Mindestanzahl erreicht ist
        $i = 0;
        while (count($uebungen) < $minUebungen && $i < count($zusaetzlicheUebungen)) {
            $uebungen[] = $zusaetzlicheUebungen[$i];
            $i++;
        }
        
        return $uebungen;
    }
    
    /**
     * Verteilt Übungen auf die Trainingstage basierend auf Split-Typ
     */
    private function verteileUebungenAufTage(
        array $uebungen, 
        array $uebungenNachKoerperbereich, 
        array $uebungenNachMuskelgruppe, 
        array $wochentage, 
        string $splitTyp
    ): array {
        $uebungenNachTag = [];
        
        switch ($splitTyp) {
            case 'Ganzkörper':
                $uebungenNachTag = $this->verteileGanzkoerperUebungen($uebungen, $uebungenNachMuskelgruppe, $wochentage);
                break;
                
            case 'Zweier-Split':
                $uebungenNachTag = $this->verteileZweierSplitUebungen($uebungenNachKoerperbereich, $wochentage);
                break;
                
            case 'Dreier-Split':
                $uebungenNachTag = $this->verteileDreierSplitUebungen($uebungenNachKoerperbereich, $uebungenNachMuskelgruppe, $wochentage);
                break;
        }
        
        // Sicherstellen, dass jeder Tag genügend Übungen hat
        foreach ($wochentage as $tag) {
            if (!isset($uebungenNachTag[$tag])) {
                $uebungenNachTag[$tag] = [];
            }
            
            // Wenn zu wenig Übungen, füge andere hinzu
            if (count($uebungenNachTag[$tag]) < 7) {
                foreach ($uebungen as $uebung) {
                    $bereitsVorhanden = false;
                    foreach ($uebungenNachTag[$tag] as $vorhandeneUebung) {
                        if ($vorhandeneUebung['uebung_id'] == $uebung['uebung_id']) {
                            $bereitsVorhanden = true;
                            break;
                        }
                    }
                    
                    if (!$bereitsVorhanden && count($uebungenNachTag[$tag]) < 7) {
                        $uebungenNachTag[$tag][] = $uebung;
                    }
                }
            }
            
            // Maximal 7 Übungen pro Tag
            if (count($uebungenNachTag[$tag]) > 7) {
                $uebungenNachTag[$tag] = array_slice($uebungenNachTag[$tag], 0, 7);
            }
        }
        
        return $uebungenNachTag;
    }
    
    /**
     * Verteilt Übungen für einen Ganzkörper-Trainingsplan
     */
    private function verteileGanzkoerperUebungen(array $uebungen, array $uebungenNachMuskelgruppe, array $wochentage): array
    {
        $uebungenNachTag = [];
        
        // Für Ganzkörper-Training: Verteile Übungen gleichmäßig auf alle Tage,
        // aber stelle sicher, dass jeder Tag eine gute Mischung aus verschiedenen Muskelgruppen hat
        
        // Sortiere Muskelgruppen nach Priorität (große Muskelgruppen zuerst)
        $muskelgruppenPrioritaet = [
            'Beine', 'Rücken', 'Brust', 'Schultern', 'Bizeps', 'Trizeps', 'Bauch'
        ];
        
        // Erstelle eine sortierte Liste von Übungen basierend auf Muskelgruppen-Priorität
        $sortiertUebungen = [];
        foreach ($muskelgruppenPrioritaet as $muskelgruppe) {
            if (isset($uebungenNachMuskelgruppe[$muskelgruppe])) {
                foreach ($uebungenNachMuskelgruppe[$muskelgruppe] as $uebung) {
                    $sortiertUebungen[] = $uebung;
                }
            }
        }
        
        // Füge alle übrigen Übungen hinzu, die nicht in den Prioritätsmuskelgruppen waren
        foreach ($uebungen as $uebung) {
            $bereitsVorhanden = false;
            foreach ($sortiertUebungen as $sortiertUebung) {
                if ($sortiertUebung['uebung_id'] == $uebung['uebung_id']) {
                    $bereitsVorhanden = true;
                    break;
                }
            }
            
            if (!$bereitsVorhanden) {
                $sortiertUebungen[] = $uebung;
            }
        }
        
        // Gleichmäßige Verteilung der Übungen auf alle Trainingstage
        $uebungenProTag = ceil(count($sortiertUebungen) / count($wochentage));
        $uebungIndex = 0;
        
        foreach ($wochentage as $tag) {
            $uebungenNachTag[$tag] = [];
            for ($i = 0; $i < $uebungenProTag && $uebungIndex < count($sortiertUebungen); $i++) {
                $uebungenNachTag[$tag][] = $sortiertUebungen[$uebungIndex];
                $uebungIndex++;
            }
        }
        
        return $uebungenNachTag;
    }
    
    /**
     * Verteilt Übungen für einen Zweier-Split-Trainingsplan
     */
    private function verteileZweierSplitUebungen(array $uebungenNachKoerperbereich, array $wochentage): array
    {
        $uebungenNachTag = [];
        
        // Aufteilung in Oberkörper und Beine
        $oberkörperTage = [];
        $beineTage = [];
        
        // Wechselnde Zuweisung der Tage zu Oberkörper und Beinen
        for ($i = 0; $i < count($wochentage); $i++) {
            if ($i % 2 == 0) {
                $oberkörperTage[] = $wochentage[$i];
            } else {
                $beineTage[] = $wochentage[$i];
            }
        }
        
        // Übungen nach Körperbereich zuweisen
        foreach ($oberkörperTage as $tag) {
            $uebungenNachTag[$tag] = [];
            // Oberkörper-Übungen hinzufügen
            foreach ($uebungenNachKoerperbereich['Oberkörper'] ?? [] as $uebung) {
                $uebungenNachTag[$tag][] = $uebung;
            }
            // Rücken/Lenden-Übungen hinzufügen
            foreach ($uebungenNachKoerperbereich['Rücken/Lenden'] ?? [] as $uebung) {
                $uebungenNachTag[$tag][] = $uebung;
            }
            // Bauch-Übungen hinzufügen
            foreach ($uebungenNachKoerperbereich['Bauch'] ?? [] as $uebung) {
                $uebungenNachTag[$tag][] = $uebung;
            }
        }
        
        foreach ($beineTage as $tag) {
            $uebungenNachTag[$tag] = [];
            // Bein-Übungen hinzufügen
            foreach ($uebungenNachKoerperbereich['Beine'] ?? [] as $uebung) {
                $uebungenNachTag[$tag][] = $uebung;
            }
        }
        
        return $uebungenNachTag;
    }
    
    /**
     * Verteilt Übungen für einen Dreier-Split-Trainingsplan
     */
    private function verteileDreierSplitUebungen(array $uebungenNachKoerperbereich, array $uebungenNachMuskelgruppe, array $wochentage): array
    {
        $uebungenNachTag = [];
        
        // Aufteilung in Beine, Brust/Trizeps, Rücken/Bizeps
        $beineTage = [];
        $brustTrizepsTage = [];
        $rueckenBizepsTage = [];
        
        // Zuweisung der Tage zu den drei Bereichen
        for ($i = 0; $i < count($wochentage); $i++) {
            $modulo = $i % 3;
            if ($modulo == 0) {
                $beineTage[] = $wochentage[$i];
            } elseif ($modulo == 1) {
                $brustTrizepsTage[] = $wochentage[$i];
            } else {
                $rueckenBizepsTage[] = $wochentage[$i];
            }
        }
        
        // Beine-Tage
        foreach ($beineTage as $tag) {
            $uebungenNachTag[$tag] = [];
            // Bein-Übungen hinzufügen
            foreach ($uebungenNachKoerperbereich['Beine'] ?? [] as $uebung) {
                if (count($uebungenNachTag[$tag]) < 7) {
                    $uebungenNachTag[$tag][] = $uebung;
                }
            }
        }
        
        // Brust/Trizeps-Tage
        foreach ($brustTrizepsTage as $tag) {
            $uebungenNachTag[$tag] = [];
            // Brust-Übungen hinzufügen
            foreach ($uebungenNachMuskelgruppe['Brust'] ?? [] as $uebung) {
                if (count($uebungenNachTag[$tag]) < 5) {
                    $uebungenNachTag[$tag][] = $uebung;
                }
            }
            // Trizeps-Übungen hinzufügen
            foreach ($uebungenNachMuskelgruppe['Trizeps'] ?? [] as $uebung) {
                if (count($uebungenNachTag[$tag]) < 7) {
                    $uebungenNachTag[$tag][] = $uebung;
                }
            }
        }
        
        // Rücken/Bizeps-Tage
        foreach ($rueckenBizepsTage as $tag) {
            $uebungenNachTag[$tag] = [];
            // Rücken-Übungen hinzufügen
            foreach ($uebungenNachMuskelgruppe['Rücken'] ?? [] as $uebung) {
                if (count($uebungenNachTag[$tag]) < 5) {
                    $uebungenNachTag[$tag][] = $uebung;
                }
            }
            // Bizeps-Übungen hinzufügen
            foreach ($uebungenNachMuskelgruppe['Bizeps'] ?? [] as $uebung) {
                if (count($uebungenNachTag[$tag]) < 7) {
                    $uebungenNachTag[$tag][] = $uebung;
                }
            }
        }
        
        return $uebungenNachTag;
    }
    
    /**
     * Bestimmt die Trainingsparameter basierend auf Trainingsart und Erholungszeit
     */
    private function bestimmeTrainingsParameter(string $trainingsart, string $erholungszeit): array
    {
        // Standardwerte
        $saetze = 3;
        $wiederholungen = 12;
        $pause = 60; // in Sekunden
        $gewichtProzent = 60;
        
        // Anpassung basierend auf Trainingsart
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
        
        // Anpassung basierend auf Erholungszeit
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
        
        return [
            'saetze' => $saetze,
            'wiederholungen' => $wiederholungen,
            'pause' => $pause,
            'gewichtProzent' => $gewichtProzent
        ];
    }
    
    /**
     * Bestimmt das Gewicht für eine Übung
     */
    private function bestimmeGewicht(
        array $uebung, 
        int $mitgliedId, 
        int $gewichtProzent, 
        bool $useMaximalkraft, 
        bool $manuelleGewichte, 
        array $data
    ): float {
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
        
        // Wenn kein Gewicht bestimmt werden konnte, Standardwert basierend auf Übungstyp und Schwierigkeitsgrad
        if ($gewicht <= 0) {
            // Standardgewichte basierend auf Übungstyp
            switch ($uebung['uebungstyp']) {
                case 'Geführtes Kraftgerät':
                    $gewicht = rand(10, 30);
                    break;
                case 'Freihantel':
                    $gewicht = rand(5, 20);
                    break;
                case 'Kabelzug':
                    $gewicht = rand(10, 25);
                    break;
                case 'Eigengewicht':
                    $gewicht = 0; // Eigengewichtsübungen haben kein zusätzliches Gewicht
                    break;
                default:
                    $gewicht = rand(5, 15);
            }
            
            // Anpassung basierend auf Muskelgruppe
            if (stripos($uebung['muskelgruppe'], 'Beine') !== false) {
                $gewicht *= 1.5; // Beinübungen verwenden in der Regel mehr Gewicht
            } elseif (stripos($uebung['muskelgruppe'], 'Rücken') !== false) {
                $gewicht *= 1.3; // Rückenübungen verwenden auch mehr Gewicht
            }
            
            // Auf 0.5 runden
            $gewicht = round($gewicht * 2) / 2;
        }
        
        return $gewicht;
    }
    
    /**
     * Bestimmt die Technik für eine Übung
     */
    private function bestimmeTechnik(bool $spezielleTechniken): string
    {
        $technik = 'Normal';
        
        if ($spezielleTechniken) {
            // 20% Chance auf spezielle Technik
            if (rand(1, 5) == 1) {
                $technik = (rand(0, 1) == 0) ? 'Supersatz' : 'Dropset';
            }
        }
        
        return $technik;
    }
}