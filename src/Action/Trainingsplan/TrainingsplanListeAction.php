<?php

namespace App\Action\Trainingsplan;

use App\Domain\Trainingsplan\TrainingsplanRepository;
use App\Domain\Mitglied\MitgliedRepository;
use App\Domain\Uebungen\UebungRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class TrainingsplanListeAction
{
    private TrainingsplanRepository $trainingsplanRepo;
    private MitgliedRepository $mitgliedRepo;
    private UebungRepository $uebungRepo;
    private Twig $view;

    public function __construct(
        TrainingsplanRepository $trainingsplanRepo,
        MitgliedRepository $mitgliedRepo,
        UebungRepository $uebungRepo,
        Twig $twig
    ) {
        $this->trainingsplanRepo = $trainingsplanRepo;
        $this->mitgliedRepo = $mitgliedRepo;
        $this->uebungRepo = $uebungRepo;
        $this->view = $twig;
    }

    /**
     * Zeigt alle Trainingspläne an
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Alle Trainingspläne mit Mitgliedsinformationen abrufen
        $trainingsplaene = $this->trainingsplanRepo->findAllWithMitglieder();
        
        // Für jeden Trainingsplan die zugehörigen Übungen abrufen
        foreach ($trainingsplaene as &$plan) {
            $plan['uebungen'] = $this->trainingsplanRepo->findUebungenByPlanId($plan['trainingsplan_id']);
            $plan['uebungen_count'] = count($plan['uebungen']);
            
            // Gruppieren der Übungen nach Trainingstagen
            $plan['uebungen_nach_tag'] = [];
            foreach ($plan['uebungen'] as $uebung) {
                $tag = $uebung['trainingstag'];
                if (!isset($plan['uebungen_nach_tag'][$tag])) {
                    $plan['uebungen_nach_tag'][$tag] = [];
                }
                $plan['uebungen_nach_tag'][$tag][] = $uebung;
            }
        }
        
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        
        $data = [
            'title' => 'Alle Trainingspläne',
            'trainingsplaene' => $trainingsplaene,
            'backUrl' => $routeParser->urlFor('home')
        ];
        
        return $this->view->render($response, 'trainingsplan/liste.twig', $data);
    }

    /**
     * Zeigt alle Trainingspläne eines bestimmten Mitglieds an
     */
    public function forMember(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id'];
        
        // Prüfen, ob das Mitglied existiert
        $mitglied = $this->mitgliedRepo->findById($mitgliedId);
        if (!$mitglied) {
            return $response->withStatus(404);
        }
        
        // Trainingspläne des Mitglieds abrufen
        $trainingsplaene = $this->trainingsplanRepo->findAllByMitgliedId($mitgliedId);
        
        // Für jeden Trainingsplan die zugehörigen Übungen abrufen
        foreach ($trainingsplaene as &$plan) {
            $plan['uebungen'] = $this->trainingsplanRepo->findUebungenByPlanId($plan['trainingsplan_id']);
            $plan['uebungen_count'] = count($plan['uebungen']);
            
            // Gruppieren der Übungen nach Trainingstagen
            $plan['uebungen_nach_tag'] = [];
            foreach ($plan['uebungen'] as $uebung) {
                $tag = $uebung['trainingstag'];
                if (!isset($plan['uebungen_nach_tag'][$tag])) {
                    $plan['uebungen_nach_tag'][$tag] = [];
                }
                $plan['uebungen_nach_tag'][$tag][] = $uebung;
            }
        }
        
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        
        $data = [
            'title' => 'Trainingspläne für ' . $mitglied['vorname'] . ' ' . $mitglied['nachname'],
            'mitglied' => $mitglied,
            'trainingsplaene' => $trainingsplaene,
            'createUrl' => $routeParser->urlFor('trainingsplan-generieren', ['id' => $mitgliedId]),
            'backUrl' => $routeParser->urlFor('mitglied-details', ['id' => $mitgliedId])
        ];
        
        return $this->view->render($response, 'trainingsplan/liste.twig', $data);
    }
}