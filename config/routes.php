<?php

// Define app routes

use App\Action\Kurse\KurseListeAction;
use App\Action\Mitglied\MitgliederListeAction;
use App\Action\Mitglied\MitgliedDetailsAction;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $twig = $app->getContainer()->get(Twig::class);
    $app->add(TwigMiddleware::create($app, $twig));

    $app->addBodyParsingMiddleware();
;


    // Mitglieder Routen
    $app->get('/mitglieder', \App\Action\Mitglied\MitgliederListeAction::class)->setName('mitglieder-liste');
    $app->get('/mitglieder/{id}', \App\Action\Mitglied\MitgliedDetailsAction::class)->setName('mitglied-details');
    $app->get('/kurse', \App\Action\Kurse\KurseListeAction::class)->setName('kurse-liste');
    $app->get('/home', \App\Action\home\homeAction::class)->setName('home');

   
};
