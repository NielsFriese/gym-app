<?php

// Define app routes
use App\Action\Kurse\KurseErstellenAction;
use App\Action\Kurse\KurseListeAction;
use App\Action\Mitglied\MitgliederListeAction;
use App\Action\Mitglied\MitgliedDetailsAction;
use App\Action\Mitglied\MitgliedErstellenAction;
use App\Action\Mitglied\MitgliedLoeschenAction;
use App\Action\Mitglied\MitgliedBearbeitenAction;
use App\Action\Mitglied\MitgliedInfoErstellenAction;
use App\Action\Mitglied\MitgliedInfoAnzeigenAction;
use App\Action\Mitglied\MitgliedInfoBearbeitenAction;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $twig = $app->getContainer()->get(Twig::class);
    $app->add(TwigMiddleware::create($app, $twig));

    $app->addBodyParsingMiddleware(); 

    // Mitglieder Routen
    $app->get('/mitglieder', \App\Action\Mitglied\MitgliederListeAction::class)->setName('mitglieder-liste');
    $app->get('/mitglieder/loeschen/{id}', \App\Action\Mitglied\MitgliedLoeschenAction::class)->setName('mitglied-loeschen');

    $app->get('/mitglieder/neu', [\App\Action\Mitglied\MitgliedErstellenAction::class, 'showForm'])->setName('mitglied-erstellen');
    $app->post('/mitglieder/neu', [\App\Action\Mitglied\MitgliedErstellenAction::class, 'handleSubmit'])->setName('mitglied-erstellen-submit');
    // Mitglied Info Erstellen Routen
    
    $app->get('/mitglieder/{id:[0-9]+}/info/neu', [\App\Action\Mitglied\MitgliedInfoErstellenAction::class, 'showForm'])->setName('mitglied-info-erstellen');
    $app->post('/mitglieder/{id:[0-9]+}/info/neu', [\App\Action\Mitglied\MitgliedInfoErstellenAction::class, 'handleSubmit'])->setName('mitglied-info-erstellen-submit');
    // Mitglied Bearbeiten Routen
    $app->get('/mitglieder/bearbeiten/{id:[0-9]+}', [MitgliedBearbeitenAction::class, 'showEditForm'])->setName('mitglied-bearbeiten');
    $app->post('/mitglieder/bearbeiten/{id:[0-9]+}', [MitgliedBearbeitenAction::class, 'handleEditSubmit'])->setName('mitglied-bearbeiten-submit');
    
    $app->get('/mitglieder/{id:[0-9]+}/info/bearbeiten', [\App\Action\Mitglied\MitgliedInfoBearbeitenAction::class, 'showEditForm'])->setName('mitglied-info-bearbeiten');
    $app->post('/mitglieder/{id:[0-9]+}/info/bearbeiten', [\App\Action\Mitglied\MitgliedInfoBearbeitenAction::class, 'handleEditSubmit'])->setName('mitglied-info-bearbeiten-submit');
    // Mitglied Detalis Routen
    $app->get('/mitglieder/{id}', \App\Action\Mitglied\MitgliedDetailsAction::class)->setName('mitglied-details');
    $app->get('/mitglieder/{id:[0-9]+}/info', [\App\Action\Mitglied\MitgliedInfoAnzeigenAction::class, '__invoke'])->setName('mitglied-info-anzeigen');


    // Kurse Routen
    $app->get('/kurse', \App\Action\Kurse\KurseListeAction::class)->setName('kurse-liste');


    $app->get('/kurse/neu', [\App\Action\Kurse\KurseErstellenAction::class, 'showForm'])->setName('kurse-erstellen');
    $app->post('/kurse/neu', [\App\Action\Kurse\KurseErstellenAction::class, 'handleSubmit'])->setName('kurse-erstellen-submit');


    // Allgemeine Routen
    $app->get('/home', \App\Action\home\homeAction::class)->setName('home');
};