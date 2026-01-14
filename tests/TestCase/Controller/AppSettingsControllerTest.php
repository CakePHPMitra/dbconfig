<?php
declare(strict_types=1);

namespace DbConfig\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * AppSettingsController Test Case
 *
 * Tests the security-related functionality of the AppSettingsController.
 */
class AppSettingsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'DbConfig.AppSettings',
    ];

    /**
     * Setup method
     */
    public function setUp(): void
    {
        parent::setUp();

        // Configure permissions for testing
        Configure::write('DbConfig.permissions', [
            'roleAttribute' => 'role',
            'viewRoles' => ['admin', 'manager'],
            'updateRoles' => ['admin'],
            'bypassRoles' => ['superadmin'],
            'unauthenticatedAction' => 'deny',
        ]);
    }

    /**
     * Teardown method
     */
    public function tearDown(): void
    {
        Configure::delete('DbConfig.permissions');
        parent::tearDown();
    }

    /**
     * Test index requires authentication
     */
    public function testIndexRequiresAuthentication(): void
    {
        $this->get('/db-config/app-settings');

        $this->assertResponseCode(401);
    }

    /**
     * Test index accessible with proper role
     */
    public function testIndexAccessibleWithProperRole(): void
    {
        // Set up authenticated session with admin role
        $this->session([
            'Auth' => ['id' => 1, 'role' => 'admin'],
        ]);

        // Also set the identity attribute for the request
        $identity = ['id' => 1, 'role' => 'admin'];

        // Configure to use session resolver
        Configure::write('DbConfig.permissions.identityResolver', 'session');

        $this->get('/db-config/app-settings');

        // Should either succeed or redirect (depends on full app setup)
        $this->assertResponseCode(200);
    }

    /**
     * Test index denied for unauthorized role
     */
    public function testIndexDeniedForUnauthorizedRole(): void
    {
        $this->session([
            'Auth' => ['id' => 2, 'role' => 'guest'],
        ]);

        Configure::write('DbConfig.permissions.identityResolver', 'session');

        $this->get('/db-config/app-settings');

        $this->assertResponseCode(403);
    }

    /**
     * Test update requires proper permission
     */
    public function testUpdateRequiresPermission(): void
    {
        // User with view but not update permission
        $this->session([
            'Auth' => ['id' => 1, 'role' => 'manager'],
        ]);

        Configure::write('DbConfig.permissions.identityResolver', 'session');

        $this->post('/db-config/app-settings?id=1', [
            'value' => 'America/New_York',
        ]);

        // Should redirect with error (no update permission)
        $this->assertRedirect(['action' => 'index']);
    }

    /**
     * Test bypass role has full access
     */
    public function testBypassRoleHasFullAccess(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'role' => 'superadmin'],
        ]);

        Configure::write('DbConfig.permissions.identityResolver', 'session');

        $this->get('/db-config/app-settings');

        $this->assertResponseCode(200);
    }

    /**
     * Test blocked config key cannot be saved
     */
    public function testBlockedConfigKeyCannotBeSaved(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'role' => 'admin'],
        ]);

        Configure::write('DbConfig.permissions.identityResolver', 'session');

        // Try to create a setting with blocked key
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/db-config/app-settings', [
            'module' => 'App',
            'config_key' => 'Security.salt', // This is a blocked key
            'value' => 'malicious_value',
            'type' => 'string',
        ]);

        // Should fail validation
        $this->assertResponseContains('not allowed');
    }

    /**
     * Test CSRF protection is enabled
     */
    public function testCsrfProtectionEnabled(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'role' => 'admin'],
        ]);

        Configure::write('DbConfig.permissions.identityResolver', 'session');

        // POST without CSRF token should fail
        $this->post('/db-config/app-settings?id=1', [
            'value' => 'test',
        ]);

        // Should get CSRF error (403 or similar)
        $this->assertResponseError();
    }
}
