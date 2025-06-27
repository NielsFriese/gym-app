<?php

namespace App\Action\Mitglied;

use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class MitgliedInfoBearbeitenAction
{
    private MitgliedRepository $mitgliedRepo;
    private Twig $view;

    public function __construct(MitgliedRepository $mitgliedRepo, Twig $twig)
    {
        $this->mitgliedRepo = $mitgliedRepo;
        $this->view = $twig;
    }

    public function showEditForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id'];
        $mitglied = $this->mitgliedRepo->findByIdWithInfo($mitgliedId);
        
        if (!$mitglied) {
            return $response->withStatus(404);
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $data = [
            'title' => 'Informationen bearbeiten - ' . $mitglied['vorname'] . ' ' . $mitglied['nachname'],
            'formAction' => $routeParser->urlFor('mitglied-info-bearbeiten-submit', ['id' => $mitgliedId]),
            'mitglied' => $mitglied,
            'isEditMode' => true,
            'geschlechterOptionen' => [
                ['value' => 'm', 'label' => 'Männlich'],
                ['value' => 'w', 'label' => 'Weiblich'],
                ['value' => 'd', 'label' => 'Divers']
            ],
        ];

        // GEÄNDERT: Verwendet jetzt info-formular.twig
        return $this->view->render($response, 'mitglieder/info-formular.twig', $data);
    }

    public function handleEditSubmit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id'];
        $formularDaten = $request->getParsedBody();
        
        $mitglied = $this->mitgliedRepo->findById($mitgliedId);
        if (!$mitglied) {
            return $response->withStatus(404);
        }

        try {
            $this->mitgliedRepo->updateInfo($mitgliedId, [
                'gewicht' => !empty($formularDaten['gewicht']) ? (float)$formularDaten['gewicht'] : null,
                'groesse' => !empty($formularDaten['groesse']) ? (float)$formularDaten['groesse'] : null,
                'geschlecht' => !empty($formularDaten['geschlecht']) ? $formularDaten['geschlecht'] : null,
                'weitere_informationen' => !empty($formularDaten['weitere_informationen']) ? $formularDaten['weitere_informationen'] : null,
            ]);

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $url = $routeParser->urlFor('mitglied-info-anzeigen', ['id' => $mitgliedId]);

            return $response->withHeader('Location', $url)->withStatus(302);
            
        } catch (\Exception $e) {
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            
            $data = [
                'title' => 'Informationen bearbeiten - ' . $mitglied['vorname'] . ' ' . $mitglied['nachname'],
                'formAction' => $routeParser->urlFor('mitglied-info-bearbeiten-submit', ['id' => $mitgliedId]),
                'mitglied' => $mitglied,
                'error' => 'Fehler beim Speichern der Informationen. Bitte versuchen Sie es erneut.',
                'formData' => $formularDaten,
                'isEditMode' => true,
                'geschlechterOptionen' => [
                    ['value' => 'm', 'label' => 'Männlich'],
                    ['value' => 'w', 'label' => 'Weiblich'],
                    ['value' => 'd', 'label' => 'Divers']
                ],
            ];

            // GEÄNDERT: Verwendet jetzt info-formular.twig
            return $this->view->render($response, 'mitglieder/info-formular.twig', $data);
        }
    }
}