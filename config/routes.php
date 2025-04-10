<?php

// Define app routes

use App\Action\Mitglied\MitgliederListeAction;
use App\Action\Mitglied\MitgliedDetailsAction;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $twig = $app->getContainer()->get(Twig::class);
    $app->add(TwigMiddleware::create($app, $twig));

    $app->addBodyParsingMiddleware();

    $app->get('/home', \App\Action\Home\HomeAction::class)->setName('home');
    $app->get('/alter/{name}/{jahr}', \App\Action\Home\AlterrechnerAction::class);
    $app->get('/bmi1/{grösse}/{gewicht}', \App\Action\Home\HalloAction::class);


    // Mitglieder Routen
    $app->get('/mitglieder', \App\Action\Mitglied\MitgliederListeAction::class)->setName('mitglieder-liste');
    $app->get('/mitglieder/{id}', \App\Action\Mitglied\MitgliedDetailsAction::class)->setName('mitglied-details');
    
};
