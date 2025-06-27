<?php

namespace App\Action\Maximalkraft;

use App\Domain\Maximalkraft\MaximalkraftTestRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

final class MaximalkraftTestLoeschenAction
{
    private MaximalkraftTestRepository $testRepo;

    public function __construct(MaximalkraftTestRepository $testRepo)
    {
        $this->testRepo = $testRepo;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $testId = (int)$args['test_id'];
        
        // Test abrufen, um die mitglied_id zu erhalten
        $test = $this->testRepo->findById($testId);
        if (!$test) {
            return $response->withStatus(404);
        }
        
        $mitgliedId = (int)$test['mitglied_id'];
        
        // Test löschen
        $this->testRepo->delete($testId);
        
        // Zurück zur Testliste
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $url = $routeParser->urlFor('maximalkraft-tests', ['id' => $mitgliedId]);
        
        return $response->withHeader('Location', $url)->withStatus(302);
    }
}