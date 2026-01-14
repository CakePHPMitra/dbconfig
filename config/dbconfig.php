<?php
declare(strict_types=1);

/**
 * DbConfig Plugin Configuration
 *
 * This file contains default configuration for the DbConfig plugin.
 * Host applications can override these settings in their config/app_local.php.
 */
return [
    'DbConfig' => [
        /**
         * Permission settings for the DbConfig plugin
         */
        'permissions' => [
            /**
             * The identity attribute that contains the user's role.
             * Different apps may use 'role', 'user_type', 'group_id', etc.
             * Supports dot notation for nested attributes (e.g., 'role.name')
             */
            'roleAttribute' => 'role',

            /**
             * Roles that can VIEW settings (index action, read-only)
             * Use '*' to allow all authenticated users
             */
            'viewRoles' => ['admin', 'super_admin'],

            /**
             * Roles that can UPDATE settings (edit/save operations)
             */
            'updateRoles' => ['admin', 'super_admin'],

            /**
             * Bypass roles - these roles ALWAYS have full access
             * regardless of viewRoles/updateRoles settings
             */
            'bypassRoles' => ['super_admin'],

            /**
             * What to do when user is not authenticated:
             * - 'redirect': Redirect to login URL
             * - 'deny': Show 403 forbidden
             * - 'allow': Allow unauthenticated access (not recommended)
             */
            'unauthenticatedAction' => 'redirect',

            /**
             * Login URL for redirect (only used when unauthenticatedAction = 'redirect')
             * Can be array (CakePHP route) or string URL
             */
            'loginUrl' => ['controller' => 'Users', 'action' => 'login', 'plugin' => null],

            /**
             * Method to retrieve identity from request
             * - 'attribute': Use $request->getAttribute('identity')
             * - 'session': Use $request->getSession()->read('Auth')
             * - callable: Custom callable receiving $request
             */
            'identityResolver' => 'attribute',
        ],
    ],
];
