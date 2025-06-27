<?php

// Define app routes
use App\Action\Kurse\KurseErstellenAction;
use App\Action\Kurse\KurseListeAction;
use App\Action\Mitglied\MitgliederListeAction;
use App\Action\Mitglied\MitgliedInfoAnzeigenAction;
use App\Action\Mitglied\MitgliedErstellenAction;
use App\Action\Mitglied\MitgliedLoeschenAction;
use App\Action\Mitglied\MitgliedBearbeitenAction;
use App\Action\Mitglied\MitgliedInfoErstellenAction;
use App\Action\Mitglied\MitgliedInfoBearbeitenAction;
use App\Action\Maximalkraft\MaximalkraftTestListeAction;
use App\Action\Maximalkraft\MaximalkraftTestErstellenAction;
use App\Action\Maximalkraft\MaximalkraftTestBearbeitenAction;
use App\Action\Maximalkraft\MaximalkraftTestLoeschenAction;
use App\Action\Home\HomeAction;
// Neue Trainingsplan-Action-Klassen
use App\Action\Trainingsplan\TrainingsplanGenerierenAction;
use App\Action\Trainingsplan\TrainingsplanListeAction;
use App\Action\Trainingsplan\TrainingsplanAnzeigenAction;
use App\Action\Trainingsplan\TrainingsplanBearbeitenAction;
use App\Action\Trainingsplan\TrainingsplanLoeschenAction;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $twig = $app->getContainer()->get(Twig::class);
    $app->add(TwigMiddleware::create($app, $twig));

    $app->addBodyParsingMiddleware(); 

    // Mitglieder Routen
    $app->get('/mitglieder', MitgliederListeAction::class)->setName('mitglieder-liste');
    $app->get('/mitglieder/loeschen/{id}', MitgliedLoeschenAction::class)->setName('mitglied-loeschen');

    $app->get('/mitglieder/neu', [MitgliedErstellenAction::class, 'showForm'])->setName('mitglied-erstellen');
    $app->post('/mitglieder/neu', [MitgliedErstellenAction::class, 'handleSubmit'])->setName('mitglied-erstellen-submit');
    
    // Mitglied Info Erstellen Routen
    $app->get('/mitglieder/{id:[0-9]+}/info/neu', [MitgliedInfoErstellenAction::class, 'showForm'])->setName('mitglied-info-erstellen');
    $app->post('/mitglieder/{id:[0-9]+}/info/neu', [MitgliedInfoErstellenAction::class, 'handleSubmit'])->setName('mitglied-info-erstellen-submit');
    
    // Mitglied Bearbeiten Routen
    $app->get('/mitglieder/bearbeiten/{id:[0-9]+}', [MitgliedBearbeitenAction::class, 'showEditForm'])->setName('mitglied-bearbeiten');
    $app->post('/mitglieder/bearbeiten/{id:[0-9]+}', [MitgliedBearbeitenAction::class, 'handleEditSubmit'])->setName('mitglied-bearbeiten-submit');
    
    $app->get('/mitglieder/{id:[0-9]+}/info/bearbeiten', [MitgliedInfoBearbeitenAction::class, 'showEditForm'])->setName('mitglied-info-bearbeiten');
    $app->post('/mitglieder/{id:[0-9]+}/info/bearbeiten', [MitgliedInfoBearbeitenAction::class, 'handleEditSubmit'])->setName('mitglied-info-bearbeiten-submit');
    
    // Mitglied Details Routen - GEÄNDERT: Jetzt zeigt die MitgliedInfoAnzeigenAction alle Details an
    $app->get('/mitglieder/{id}', MitgliedInfoAnzeigenAction::class)->setName('mitglied-details');
    
    // Diese Route kann entfernt werden, da wir jetzt nur noch eine Ansicht haben
    //$app->get('/mitglieder/{id:[0-9]+}/info', [MitgliedInfoAnzeigenAction::class, '__invoke'])->setName('mitglied-info-anzeigen');

    // Kurse Routen
    $app->get('/kurse', KurseListeAction::class)->setName('kurse-liste');

    $app->get('/kurse/neu', [KurseErstellenAction::class, 'showForm'])->setName('kurse-erstellen');
    $app->post('/kurse/neu', [KurseErstellenAction::class, 'handleSubmit'])->setName('kurse-erstellen-submit');

    // Maximalkraft-Test Routen
    $app->get('/mitglieder/{id:[0-9]+}/maximalkraft-tests', MaximalkraftTestListeAction::class)->setName('maximalkraft-tests');
    
    $app->get('/mitglieder/{id:[0-9]+}/maximalkraft-tests/neu', [MaximalkraftTestErstellenAction::class, 'showForm'])->setName('maximalkraft-test-erstellen');
    $app->post('/mitglieder/{id:[0-9]+}/maximalkraft-tests/neu', [MaximalkraftTestErstellenAction::class, 'handleSubmit'])->setName('maximalkraft-test-erstellen-submit');
    
    $app->get('/maximalkraft-tests/bearbeiten/{test_id:[0-9]+}', [MaximalkraftTestBearbeitenAction::class, 'showEditForm'])->setName('maximalkraft-test-bearbeiten');
    $app->post('/maximalkraft-tests/bearbeiten/{test_id:[0-9]+}', [MaximalkraftTestBearbeitenAction::class, 'handleEditSubmit'])->setName('maximalkraft-test-bearbeiten-submit');
    
    $app->get('/maximalkraft-tests/loeschen/{test_id:[0-9]+}', MaximalkraftTestLoeschenAction::class)->setName('maximalkraft-test-loeschen');

    // Neue Trainingsplan-Routen
    $app->get('/trainingsplaene', TrainingsplanListeAction::class)->setName('trainingsplan-liste');
    
    // Trainingsplan für ein Mitglied generieren
    $app->get('/mitglieder/{id:[0-9]+}/trainingsplan/generieren', [TrainingsplanGenerierenAction::class, 'showForm'])->setName('trainingsplan-generieren');
    $app->post('/mitglieder/{id:[0-9]+}/trainingsplan/generieren', [TrainingsplanGenerierenAction::class, 'handleSubmit'])->setName('trainingsplan-generieren-submit');
    
    // Trainingsplan anzeigen
    $app->get('/trainingsplaene/{plan_id:[0-9]+}', TrainingsplanAnzeigenAction::class)->setName('trainingsplan-anzeigen');
    
    // Trainingsplan bearbeiten
    $app->get('/trainingsplaene/bearbeiten/{plan_id:[0-9]+}', [TrainingsplanBearbeitenAction::class, 'showEditForm'])->setName('trainingsplan-bearbeiten');
    $app->post('/trainingsplaene/bearbeiten/{plan_id:[0-9]+}', [TrainingsplanBearbeitenAction::class, 'handleEditSubmit'])->setName('trainingsplan-bearbeiten-submit');
    
    // Trainingsplan löschen
    $app->get('/trainingsplaene/loeschen/{plan_id:[0-9]+}', TrainingsplanLoeschenAction::class)->setName('trainingsplan-loeschen');
    
    // Alle Trainingspläne eines Mitglieds anzeigen
    $app->get('/mitglieder/{id:[0-9]+}/trainingsplaene', [TrainingsplanListeAction::class, 'forMember'])->setName('mitglied-trainingsplaene');

    // Allgemeine Routen
    $app->get('/home', HomeAction::class)->setName('home');
};