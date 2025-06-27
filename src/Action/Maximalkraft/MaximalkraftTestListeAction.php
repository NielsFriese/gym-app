<?php

namespace App\Action\Maximalkraft;

use App\Domain\Maximalkraft\MaximalkraftTestRepository;
use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class MaximalkraftTestListeAction
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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id'];
        
        // Prüfen, ob das Mitglied existiert
        $mitglied = $this->mitgliedRepo->findById($mitgliedId);
        if (!$mitglied) {
            return $response->withStatus(404);
        }
        
        // Alle Tests des Mitglieds abrufen
        $tests = $this->testRepo->findAllByMitgliedId($mitgliedId);
        
        // Neueste Tests pro Übungsart abrufen (für Zusammenfassung)
        $neuesteTests = $this->testRepo->findLatestTestsPerExercise($mitgliedId);
        
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        
        $data = [
            'title' => 'Maximalkraft-Tests für ' . $mitglied['vorname'] . ' ' . $mitglied['nachname'],
            'mitglied' => $mitglied,
            'tests' => $tests,
            'neuesteTests' => $neuesteTests,
            'createUrl' => $routeParser->urlFor('maximalkraft-test-erstellen', ['id' => $mitgliedId]),
            'backUrl' => $routeParser->urlFor('mitglied-details', ['id' => $mitgliedId])
        ];
        
        return $this->view->render($response, 'maximalkraft/liste.twig', $data);
    }
}