<?php
declare(strict_types=1);

/**
 * Standalone test bootstrap for DbConfig plugin.
 *
 * This bootstrap allows running tests directly from the plugin directory
 * without requiring a host CakePHP application.
 */

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Security;

require dirname(__DIR__) . '/vendor/autoload.php';

// Load CakePHP global function aliases (h(), __(), env(), collection(), etc.)
$cakeSrc = dirname(__DIR__) . '/vendor/cakephp/cakephp/src';
require $cakeSrc . '/Core/functions_global.php';
require $cakeSrc . '/I18n/functions_global.php';
require $cakeSrc . '/Collection/functions_global.php';
require $cakeSrc . '/Error/functions_global.php';
require $cakeSrc . '/Routing/functions_global.php';

// Define path constants required by CakePHP's test infrastructure
$pluginRoot = dirname(__DIR__);
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
    define('ROOT', $pluginRoot);
}
if (!defined('APP')) {
    define('APP', $pluginRoot . DS . 'src' . DS);
}
if (!defined('CONFIG')) {
    define('CONFIG', $pluginRoot . DS . 'config' . DS);
}
if (!defined('TMP')) {
    define('TMP', sys_get_temp_dir() . DS . 'dbconfig_test' . DS);
}
if (!defined('LOGS')) {
    define('LOGS', TMP . 'logs' . DS);
}
if (!defined('CACHE')) {
    define('CACHE', TMP . 'cache' . DS);
}
if (!defined('TESTS')) {
    define('TESTS', $pluginRoot . DS . 'tests' . DS);
}
if (!defined('WWW_ROOT')) {
    define('WWW_ROOT', $pluginRoot . DS . 'webroot' . DS);
}
if (!defined('CAKE_CORE_INCLUDE_PATH')) {
    define('CAKE_CORE_INCLUDE_PATH', $pluginRoot . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
}
if (!defined('CORE_PATH')) {
    define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
}
if (!defined('CAKE')) {
    define('CAKE', CORE_PATH . 'src' . DS);
}
if (!defined('APP_DIR')) {
    define('APP_DIR', 'src');
}

// Ensure temp directories exist
@mkdir(TMP . 'cache' . DS . 'models', 0777, true);
@mkdir(TMP . 'cache' . DS . 'persistent', 0777, true);
@mkdir(TMP . 'logs', 0777, true);

// Set up minimal CakePHP configuration for testing
Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'DbConfig\Test\App',
    'encoding' => 'UTF-8',
    'defaultLocale' => 'en_US',
    'defaultTimezone' => 'UTC',
    'fullBaseUrl' => 'http://localhost',
    'paths' => [
        'plugins' => [dirname(__DIR__, 2) . DS],
        'templates' => [dirname(__DIR__) . DS . 'templates' . DS],
    ],
]);

// Set a security salt for encryption tests
Security::setSalt('a-long-but-not-random-test-security-salt-that-is-at-least-32-chars');

// Use a shared SQLite file so both default and test connections see the same data
$sqliteFile = sys_get_temp_dir() . '/dbconfig_test.sqlite';
if (file_exists($sqliteFile)) {
    unlink($sqliteFile);
}

// Configure test database connection (CakePHP fixtures use 'test' connection)
ConnectionManager::setConfig('test', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\Sqlite',
    'database' => $sqliteFile,
]);

// Default connection aliases to test
ConnectionManager::setConfig('default', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\Sqlite',
    'database' => $sqliteFile,
]);

// Create the app_settings table
$connection = ConnectionManager::get('test');
$connection->execute('
    CREATE TABLE IF NOT EXISTS app_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        module VARCHAR(255) DEFAULT NULL,
        config_key VARCHAR(255) NOT NULL,
        value TEXT NOT NULL DEFAULT "",
        type VARCHAR(255) NOT NULL DEFAULT "string",
        options TEXT DEFAULT NULL
    )
');

// Configure cache (required by CakePHP i18n/validation internals)
Cache::setConfig('_cake_translations_', [
    'engine' => 'File',
    'prefix' => 'dbconfig_test_cake_translations_',
    'path' => sys_get_temp_dir() . DS . 'cache' . DS . 'persistent' . DS,
    'serialize' => true,
    'duration' => '+10 minutes',
]);
Cache::setConfig('_cake_model_', [
    'engine' => 'File',
    'prefix' => 'dbconfig_test_cake_model_',
    'path' => sys_get_temp_dir() . DS . 'cache' . DS . 'models' . DS,
    'serialize' => true,
    'duration' => '+10 minutes',
]);

// Set encryption key for tests (required by ConfigService)
Configure::write('Settings.encryptionKey', 'test-encryption-key-for-dbconfig-plugin-at-least-32-chars-long');

// Plugin-specific test configuration
Configure::write('DbConfig.permissions', [
    'roleAttribute' => 'role',
    'viewRoles' => ['admin', 'manager'],
    'updateRoles' => ['admin'],
    'bypassRoles' => ['superadmin'],
    'unauthenticatedAction' => 'deny',
]);
