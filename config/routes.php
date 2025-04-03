<?php

// Define app routes

use Slim\App;

return function (App $app) {
    $app->get('/home', \App\Action\Home\HomeAction::class)->setName('home');
    $app->get('/ping', \App\Action\Home\PingAction::class);
    $app->get('/name/{name}', \App\Action\Home\NamenGetter::class);
    $app->get('/alter/{name}/{jahr}', \App\Action\Home\AlterrechnerAction::class);
    $app->get('/bmi1/{grösse}/{gewicht}', \App\Action\Home\HalloAction::class);
};
