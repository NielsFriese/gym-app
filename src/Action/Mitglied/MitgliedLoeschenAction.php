<?php

namespace App\Action\Mitglied;

use App\Domain\Mitglied\MitgliedRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class MitgliedLoeschenAction
{
    private MitgliedRepository $mitgliedRepo;

    private Twig $view;

    public function __construct(MitgliedRepository $mitgliedRepo, Twig $twig)
    {
        $this->mitgliedRepo = $mitgliedRepo;
        $this->view = $twig;
    }


    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $mitgliedId = (int)$args['id']; 

        $erfolg = $this->mitgliedRepo->delete($mitgliedId);

        if ($erfolg) {
            // Erfolgreiche Löschung, Umleitung zur Mitgliederliste
            // Sie müssten den Slim Router verwenden, um den Pfad zu generieren
            $routeContext = RouteContext::fromRequest($request);
            $url = $routeContext->getRouteParser()->urlFor('mitglieder-liste'); // Beispiel-Route-Name
            return $response->withHeader('Location', $url)->withStatus(302);
        } 
        
        else {

             $data = [
            'erfolg' => false,
            'titel' => 'Fehler beim Löschen des Mitglieds',
            'nachricht' => 'Das Mitglied mit der ID **' . $mitgliedId . '** konnte nicht gelöscht werden. Es existiert möglicherweise nicht oder es ist ein interner Fehler aufgetreten.',
            ];
        }
        
        return $this->view->render($response->withStatus(404), 'mitglieder/loeschen.twig', $data);
        
    }
}