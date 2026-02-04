<?php
declare(strict_types=1);

namespace DbConfig\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use DbConfig\Service\ConfigService;

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
        'plugin.DbConfig.AppSettings',
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
        $this->enableCsrfToken();
        $this->enableSecurityToken();

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
     * Test blocked config key cannot be saved via Table beforeSave
     */
    public function testBlockedConfigKeyCannotBeSaved(): void
    {
        // Verify at the Table level that blocked keys are rejected
        $table = $this->getTableLocator()->get('DbConfig.AppSettings');

        $entity = $table->newEntity([
            'module' => 'App',
            'config_key' => 'Security.salt',
            'value' => 'malicious_value',
            'type' => 'string',
        ]);

        // Validation should reject the blocked key
        $this->assertTrue($entity->hasErrors());
        $errors = $entity->getError('config_key');
        $this->assertNotEmpty($errors);

        // Even if validation is bypassed, beforeSave blocks it
        $entity->setErrors([]);
        $entity->setError('config_key', []);
        $result = $table->save($entity, ['validate' => false]);
        $this->assertFalse($result);
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

    /**
     * Test empty value submission for encrypted setting keeps existing value
     */
    public function testUpdateEncryptedWithEmptyValueKeepsExisting(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'role' => 'admin'],
        ]);

        Configure::write('DbConfig.permissions.identityResolver', 'session');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Create an encrypted setting
        $table = $this->getTableLocator()->get('DbConfig.AppSettings');
        $entity = $table->newEntity([
            'module' => 'Custom',
            'config_key' => 'Custom.test_api_key',
            'value' => 'original-secret',
            'type' => 'encrypted',
        ]);
        $saved = $table->save($entity);
        $this->assertNotFalse($saved);
        $originalValue = $table->get($saved->id)->value;

        // Submit with empty value
        $this->post('/db-config/app-settings?id=' . $saved->id, [
            'value' => '',
        ]);

        $this->assertRedirect(['action' => 'index']);

        // Verify value was not changed
        $after = $table->get($saved->id);
        $this->assertSame($originalValue, $after->value);
    }

    /**
     * Test updating encrypted value actually encrypts the new value
     */
    public function testUpdateEncryptedValueEncryptsNewValue(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'role' => 'admin'],
        ]);

        Configure::write('DbConfig.permissions.identityResolver', 'session');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Create an encrypted setting
        $table = $this->getTableLocator()->get('DbConfig.AppSettings');
        $entity = $table->newEntity([
            'module' => 'Custom',
            'config_key' => 'Custom.test_api_key',
            'value' => 'old-secret',
            'type' => 'encrypted',
        ]);
        $saved = $table->save($entity);
        $this->assertNotFalse($saved);

        // Submit with new value
        $this->post('/db-config/app-settings?id=' . $saved->id, [
            'value' => 'new-secret-value',
        ]);

        $this->assertRedirect(['action' => 'index']);

        // Verify value was encrypted (not stored as plaintext)
        $after = $table->get($saved->id);
        $this->assertNotSame('new-secret-value', $after->value);

        // Verify it decrypts correctly
        $decrypted = ConfigService::decryptValue($after->value);
        $this->assertSame('new-secret-value', $decrypted);
    }
}
