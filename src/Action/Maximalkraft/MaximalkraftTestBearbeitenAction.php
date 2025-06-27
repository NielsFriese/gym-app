<?php

namespace App\Action\Maximalkraft;

use App\Domain\Maximalkraft\MaximalkraftTestRepository;
use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class MaximalkraftTestBearbeitenAction
{
    private MaximalkraftTestRepository $testRepo;
    private MitgliedRepository $mitgliedRepo;
    private Twig $view;

    public function __construct(
        MaximalkraftTestRepository $testRepo,
        MitgliedRepository $mitgliedRepo,
        Twig $twig
    ) {
        $this->testRepo = $testRepo;
        $this->mitgliedRepo = $mitgliedRepo;
        $this->view = $twig;
    }

    public function showEditForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $testId = (int)$args['test_id'];
        
        // Test abrufen
        $test = $this->testRepo->findById($testId);
        if (!$test) {
            return $response->withStatus(404);
        }
        
        // Mitglied abrufen
        $mitgliedId = (int)$test['mitglied_id'];
        $mitglied = $this->mitgliedRepo->findById($mitgliedId);
        if (!$mitglied) {
            return $response->withStatus(404);
        }
        
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        
        $data = [
            'title' => 'Maximalkraft-Test bearbeiten',
            'formAction' => $routeParser->urlFor('maximalkraft-test-bearbeiten-submit', ['test_id' => $testId]),
            'mitglied' => $mitglied,
            'formData' => $test,
            'uebungenOptionen' => [
                ['value' => 'Beinpresse', 'label' => 'Beinpresse'],
                ['value' => 'Beinstrecker', 'label' => 'Beinstrecker'],
                ['value' => 'Brustpresse', 'label' => 'Brustpresse'],
                ['value' => 'Lat-Zug', 'label' => 'Lat-Zug'],
                ['value' => 'Rudergerät', 'label' => 'Rudergerät'],
                ['value' => 'Andere', 'label' => 'Andere']
            ],
            'backUrl' => $routeParser->urlFor('maximalkraft-tests', ['id' => $mitgliedId])
        ];
        
        return $this->view->render($response, 'maximalkraft/formular.twig', $data);
    }

    public function handleEditSubmit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $testId = (int)$args['test_id'];
        $formularDaten = $request->getParsedBody();
        
        // Test abrufen
        $test = $this->testRepo->findById($testId);
        if (!$test) {
            return $response->withStatus(404);
        }
        
        $mitgliedId = (int)$test['mitglied_id'];
        
        // Validierung
        $errors = [];
        
        if (empty($formularDaten['uebung'])) {
            $errors['uebung'] = 'Bitte wählen Sie eine Übung aus.';
        }
        
        if (empty($formularDaten['gewicht']) || !is_numeric($formularDaten['gewicht']) || $formularDaten['gewicht'] <= 0) {
            $errors['gewicht'] = 'Bitte geben Sie ein gültiges Gewicht ein.';
        }
        
        if (empty($formularDaten['wiederholungen']) || !is_numeric($formularDaten['wiederholungen']) || 
            $formularDaten['wiederholungen'] < 1 || $formularDaten['wiederholungen'] >= 37) {
            $errors['wiederholungen'] = 'Bitte geben Sie eine gültige Anzahl Wiederholungen ein (1-36).';
        }
        
        // Bei Fehlern zurück zum Formular
        if (!empty($errors)) {
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            
            $data = [
                'title' => 'Maximalkraft-Test bearbeiten',
                'formAction' => $routeParser->urlFor('maximalkraft-test-bearbeiten-submit', ['test_id' => $testId]),
                'mitglied' => $this->mitgliedRepo->findById($mitgliedId),
                'formData' => array_merge($test, $formularDaten),
                'uebungenOptionen' => [
                    ['value' => 'Beinpresse', 'label' => 'Beinpresse'],
                    ['value' => 'Beinstrecker', 'label' => 'Beinstrecker'],
                    ['value' => 'Brustpresse', 'label' => 'Brustpresse'],
                    ['value' => 'Lat-Zug', 'label' => 'Lat-Zug'],
                    ['value' => 'Rudergerät', 'label' => 'Rudergerät'],
                    ['value' => 'Andere', 'label' => 'Andere']
                ],
                'errors' => $errors,
                'backUrl' => $routeParser->urlFor('maximalkraft-tests', ['id' => $mitgliedId])
            ];
            
            return $this->view->render($response, 'maximalkraft/formular.twig', $data);
        }
        
        // Test aktualisieren
        try {
            $this->testRepo->update($testId, [
                'test_datum' => $formularDaten['test_datum'] ?? $test['test_datum'],
                'uebung' => $formularDaten['uebung'],
                'gewicht' => (float)$formularDaten['gewicht'],
                'wiederholungen' => (int)$formularDaten['wiederholungen'],
                'notizen' => $formularDaten['notizen'] ?? null
            ]);
            
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $url = $routeParser->urlFor('maximalkraft-tests', ['id' => $mitgliedId]);
            
            return $response->withHeader('Location', $url)->withStatus(302);
            
        } catch (\Exception $e) {
            // Bei Fehler zurück zum Formular mit Fehlermeldung
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            
            $data = [
                'title' => 'Maximalkraft-Test bearbeiten',
                'formAction' => $routeParser->urlFor('maximalkraft-test-bearbeiten-submit', ['test_id' => $testId]),
                'mitglied' => $this->mitgliedRepo->findById($mitgliedId),
                'formData' => array_merge($test, $formularDaten),
                'uebungenOptionen' => [
                    ['value' => 'Beinpresse', 'label' => 'Beinpresse'],
                    ['value' => 'Beinstrecker', 'label' => 'Beinstrecker'],
                    ['value' => 'Brustpresse', 'label' => 'Brustpresse'],
                    ['value' => 'Lat-Zug', 'label' => 'Lat-Zug'],
                    ['value' => 'Rudergerät', 'label' => 'Rudergerät'],
                    ['value' => 'Andere', 'label' => 'Andere']
                ],
                'error' => 'Fehler beim Aktualisieren des Tests. Bitte versuchen Sie es erneut.',
                'backUrl' => $routeParser->urlFor('maximalkraft-tests', ['id' => $mitgliedId])
            ];
            
            return $this->view->render($response, 'maximalkraft/formular.twig', $data);
        }
    }
}