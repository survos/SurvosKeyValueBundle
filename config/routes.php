<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Survos\KeyValueBundle\Controller\PixyController;

return function (RoutingConfigurator $routes) {
    $routes->add('pixy_homepage', '/pixy/{pixyName}')
        ->controller([PixyController::class, 'home'])
    ;
    $routes->add('pixy_import', '/pixy-import/{pixyName}')
        ->controller([PixyController::class, 'import'])
    ;
    $routes->add('pixy_browse', '/pixy-browse/{pixyName}/{tableName}')
        ->controller([PixyController::class, 'browse'])
    ;

    $routes->add('pixy_show_record', '/browse/{pixyName}/{tableName}/{key}')
        ->controller([PixyController::class, 'show_record'])
    ;

};
