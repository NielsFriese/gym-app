<?php

namespace App\Action\Mitglied;

use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class MitgliedErstellenAction
{
    private MitgliedRepository $mitgliedRepo;

    private Twig $view;

    public function __construct(MitgliedRepository $mitgliedRepo, Twig $twig)
    {
        $this->mitgliedRepo = $mitgliedRepo;
        $this->view = $twig;
    }

    public function showForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $data = [
            'title' => 'Neues Mitglied erstellen',
            'formAction' => $routeParser->urlFor('mitglied-erstellen-submit'),
            'mitgliedschaftsTypen' => ['Basis', 'Premium', 'VIP'],
            'mitglied' => [], // Leeres Array für den Erstellen-Modus
            'isEditMode' => false, // Explizit setzen
        ];

        return $this->view->render($response, 'mitglieder/formular.twig', $data);
    }

    public function handleSubmit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $forumularDaten = $request->getParsedBody();
    
        $mitgliedId = $this->mitgliedRepo->create([
            'vorname' => $forumularDaten['vorname'],
            'nachname' => $forumularDaten['nachname'],
            'email' => $forumularDaten['email'],
            'geburtsdatum' => $forumularDaten['geburtsdatum'],
            'beitrittsdatum' => date('Y-m-d'),
            'mitgliedschaft_typ' => $forumularDaten['mitgliedschaft_typ'],
        ]);
    
        $liste = RouteContext::fromRequest($request)->getRouteParser();
        $url = $liste->urlFor('mitglieder-liste');
    
        return $response->withHeader('Location', $url)->withStatus(302);
    }
}
