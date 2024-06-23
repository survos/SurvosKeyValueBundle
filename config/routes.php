<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Survos\KeyValueBundle\Controller\PixyController;

return function (RoutingConfigurator $routes) {
    $routes->add('pixy_homepage', '/pixy')
//        ->controller([PixyCon::class, 'commands'])
    ;

    $routes->add('survos_command', '/run-command/{commandName}')
        ->controller([CommandController::class, 'runCommand'])
    ;

};
