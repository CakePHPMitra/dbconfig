<?php
declare(strict_types=1);

namespace DbConfig\Test\App;

use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use DbConfig\DbConfigPlugin;

/**
 * Minimal Application class for standalone plugin integration testing.
 *
 * Provides the HTTP stack that CakePHP's IntegrationTestTrait requires
 * to dispatch requests during controller tests.
 */
class Application extends BaseApplication
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(): void
    {
        parent::bootstrap();

        $this->addPlugin(DbConfigPlugin::class);
    }

    /**
     * {@inheritDoc}
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new ErrorHandlerMiddleware())
            ->add(new BodyParserMiddleware())
            ->add(new RoutingMiddleware($this))
            ->add(new CsrfProtectionMiddleware());

        return $middlewareQueue;
    }

    /**
     * {@inheritDoc}
     */
    public function routes(RouteBuilder $routes): void
    {
        parent::routes($routes);
    }
}
