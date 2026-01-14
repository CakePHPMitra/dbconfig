<?php

declare(strict_types=1);

namespace DbConfig;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Cake\Utility\Hash;

/**
 * Plugin for DbConfig
 */
class DbConfigPlugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        // Load plugin configuration defaults
        $this->loadDefaultConfig();

        // Load configuration from database
        try {
            \DbConfig\Service\ConfigService::reload();
        } catch (\Exception $e) {
            \Cake\Log\Log::warning('[DbConfig] Failed to load configuration: ' . $e->getMessage());
        }
    }

    /**
     * Load default configuration for the plugin
     *
     * Plugin defaults are merged with any existing configuration,
     * allowing the host application to override settings.
     *
     * @return void
     */
    protected function loadDefaultConfig(): void
    {
        $configFile = dirname(__DIR__) . '/config/dbconfig.php';

        if (file_exists($configFile)) {
            $defaults = require $configFile;

            // Merge with any existing configuration (app can override)
            $current = Configure::read('DbConfig') ?? [];
            Configure::write('DbConfig', Hash::merge($defaults['DbConfig'] ?? [], $current));
        }
    }

    /**
     * Add routes for the plugin.
     *
     * If your plugin has many routes and you would like to isolate them into a separate file,
     * you can create `$plugin/config/routes.php` and delete this method.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'DbConfig',
            ['path' => '/db-config'],
            function (RouteBuilder $builder) {
                $builder->connect(
                    '/',
                    ['controller' => 'AppSettings', 'action' => 'index']
                );
            }
        );
        parent::routes($routes);
    }

    /**
     * Add middleware for the plugin.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }

    /**
     * Add commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands = parent::console($commands);

        return $commands;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
    }
}
