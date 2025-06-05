<?php

namespace App\Action\Mitglied;

use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class MitgliedInfoErstellenAction
{
    private MitgliedRepository $mitgliedRepo;
    private Twig $view;

    public function __construct(MitgliedRepository $mitgliedRepo, Twig $twig)
    {
        $this->mitgliedRepo = $mitgliedRepo;
        $this->view = $twig;
    }

    public function showForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id'];
        $mitglied = $this->mitgliedRepo->findById($mitgliedId);
        
        if (!$mitglied) {
            return $response->withStatus(404);
        }

        // Check if info already exists
        $existingInfo = $this->mitgliedRepo->findInfoByMitgliedId($mitgliedId);
        
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $data = [
            'title' => 'Zusätzliche Informationen erfassen',
            'formAction' => $routeParser->urlFor('mitglied-info-erstellen-submit', ['id' => $mitgliedId]),
            'mitglied' => $mitglied,
            'existingInfo' => $existingInfo,
            'geschlechterOptionen' => [
                ['value' => 'm', 'label' => 'Männlich'],
                ['value' => 'w', 'label' => 'Weiblich'],
                ['value' => 'd', 'label' => 'Divers']
            ],
        ];

        return $this->view->render($response, 'mitglieder/info-formular.twig', $data);
    }

    public function handleSubmit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id'];
        $formularDaten = $request->getParsedBody();
        
        // Validate that member exists
        $mitglied = $this->mitgliedRepo->findById($mitgliedId);
        if (!$mitglied) {
            return $response->withStatus(404);
        }

        try {
            $infoId = $this->mitgliedRepo->createInfo([
                'mitglied_id' => $mitgliedId,
                'gewicht' => !empty($formularDaten['gewicht']) ? (float)$formularDaten['gewicht'] : null,
                'groesse' => !empty($formularDaten['groesse']) ? (float)$formularDaten['groesse'] : null,
                'geschlecht' => !empty($formularDaten['geschlecht']) ? $formularDaten['geschlecht'] : null,
                'max_kraft' => !empty($formularDaten['max_kraft']) ? (float)$formularDaten['max_kraft'] : null,
                'weitere_informationen' => !empty($formularDaten['weitere_informationen']) ? $formularDaten['weitere_informationen'] : null,
            ]);

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $url = $routeParser->urlFor('mitglied-details', ['id' => $mitgliedId]);

            return $response->withHeader('Location', $url)->withStatus(302);
            
        } catch (\Exception $e) {
            // Bei Fehler zurück zum Formular mit Fehlermeldung
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            
            $data = [
                'title' => 'Zusätzliche Informationen erfassen',
                'formAction' => $routeParser->urlFor('mitglied-info-erstellen-submit', ['id' => $mitgliedId]),
                'mitglied' => $mitglied,
                'error' => 'Fehler beim Speichern der Informationen. Bitte versuchen Sie es erneut.',
                'formData' => $formularDaten,
                'geschlechterOptionen' => [
                    ['value' => 'm', 'label' => 'Männlich'],
                    ['value' => 'w', 'label' => 'Weiblich'],
                    ['value' => 'd', 'label' => 'Divers']
                ],
            ];

            return $this->view->render($response, 'mitglieder/info-formular.twig', $data);
        }
    }
}