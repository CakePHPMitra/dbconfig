<?php
declare(strict_types=1);

/**
 * Test bootstrap file for DbConfig plugin
 */

use Cake\Core\Configure;

// Load main application bootstrap
require dirname(__DIR__, 3) . '/tests/bootstrap.php';

// Plugin-specific test configuration
Configure::write('DbConfig.permissions', [
    'roleAttribute' => 'role',
    'viewRoles' => ['admin', 'manager'],
    'updateRoles' => ['admin'],
    'bypassRoles' => ['superadmin'],
    'unauthenticatedAction' => 'deny',
]);
