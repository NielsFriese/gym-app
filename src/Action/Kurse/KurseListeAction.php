<?php

namespace App\Action\Kurse;

use App\Domain\Kurse\KursRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final class KurseListeAction 
{
    private KursRepository $kursrepo;

    private Twig $view;

    public function __construct(KursRepository $kursrepo, Twig $twig)
    {
        $this->kursrepo = $kursrepo;
        $this->view = $twig;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $kurse = $this->kursrepo->findAll();
        
        
        $data = [
            'kurse' => $kurse,
            'titel' => 'Kursliste',
        ];

        return $this->view->render($response, 'mitglieder/kursliste.twig', $data);
    }
}    