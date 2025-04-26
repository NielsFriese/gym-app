<?php

// Define app routes
use App\Action\Kurse\KurseErstellenAction;
use App\Action\Kurse\KurseListeAction;
use App\Action\Mitglied\MitgliederListeAction;
use App\Action\Mitglied\MitgliedDetailsAction;
use App\Action\Mitglied\MitgliedErstellenAction;
use App\Action\Mitglied\MitgliedLoeschenAction;
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
    $app->delete('/mitglieder/loeschen/{id}', \App\Action\Mitglied\MitgliedLoeschenAction::class)->setName('mitglied-loeschen');
    

    $app->get('/mitglieder/neu', [\App\Action\Mitglied\MitgliedErstellenAction::class, 'showForm'])->setName('mitglied-erstellen');
    $app->post('/mitglieder/neu', [\App\Action\Mitglied\MitgliedErstellenAction::class, 'handleSubmit'])->setName('mitglied-erstellen-submit');

    $app->get('/mitglieder/{id}', \App\Action\Mitglied\MitgliedDetailsAction::class)->setName('mitglied-details');


   // Kurese Routen
    $app->get('/kurse', \App\Action\Kurse\KurseListeAction::class)->setName('kurse-liste');

    
    $app->get('/kurse/neu', [\App\Action\Kurse\KurseErstellenAction::class, 'showForm'])->setName('kurse-erstellen');
    $app->post('/kurse/neu', [\App\Action\Kurse\KurseErstellenAction::class, 'handleSubmit'])->setName('kurse-erstellen-submit');



    // Allgemeine Routen
    $app->get('/home', \App\Action\home\homeAction::class)->setName('home');
};  