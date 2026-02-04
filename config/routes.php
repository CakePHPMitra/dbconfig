<?php
declare(strict_types=1);

/**
 * Minimal routes file for standalone plugin testing.
 *
 * Plugin routes are defined in DbConfigPlugin::routes().
 * This file is required by CakePHP's BaseApplication.
 */

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

return static function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->fallbacks(DashedRoute::class);
    });
};
