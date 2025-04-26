<?php

namespace App\Action\Kurse;

use App\Domain\Kurse\KursRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class KurseErstellenAction
{
    private KursRepository $kursRepo;

    private Twig $view;

    public function __construct(KursRepository $kursRepo, Twig $twig)
    {
        $this->kursRepo = $kursRepo;
        $this->view = $twig;
    }

    public function showForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $data = [
            'title' => 'Neuer Kurs erstellen',
            'formAction' => $routeParser->urlFor('kurse-erstellen-submit'),
        ];

        return $this->view->render($response, 'mitglieder/formularkurse.twig', $data);
    }

    public function handleSubmit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $forumularDaten = $request->getParsedBody();
    
        $kursId = $this->kursRepo->create([
            'kursname' => $forumularDaten['kursname'],
            'beschreibung' => $forumularDaten['beschreibung'],
            'max_kapazitaet' => $forumularDaten['max_kapazitaet'],
            'trainer_id' => $forumularDaten['trainer_id'],   
        ]);
    
        $liste = RouteContext::fromRequest($request)->getRouteParser();
        $url = $liste->urlFor('kurse-liste');
    
        return $response->withHeader('Location', $url)->withStatus(302);
    }
}