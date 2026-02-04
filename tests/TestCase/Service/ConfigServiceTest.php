<?php
declare(strict_types=1);

namespace DbConfig\Test\TestCase\Service;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use DbConfig\Service\ConfigService;

/**
 * ConfigService Test Case
 *
 * Tests for the ConfigService class, particularly security-related key validation.
 */
class ConfigServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.DbConfig.AppSettings',
    ];

    /**
     * @var string|null
     */
    private ?string $originalEncryptionKey = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->originalEncryptionKey = Configure::read('Settings.encryptionKey');
    }

    public function tearDown(): void
    {
        // Restore original encryption key
        if ($this->originalEncryptionKey !== null) {
            Configure::write('Settings.encryptionKey', $this->originalEncryptionKey);
        } else {
            Configure::delete('Settings.encryptionKey');
        }
        parent::tearDown();
    }

    /**
     * Test that security-sensitive keys are blocked
     *
     * @return void
     */
    public function testBlockedKeysAreRejected(): void
    {
        // Security keys should be blocked
        $this->assertFalse(ConfigService::isKeyAllowed('Security.salt'));
        $this->assertFalse(ConfigService::isKeyAllowed('Security.key'));

        // Database credentials should be blocked
        $this->assertFalse(ConfigService::isKeyAllowed('Datasources.default.password'));
        $this->assertFalse(ConfigService::isKeyAllowed('Datasources.default.username'));

        // Email credentials should be blocked
        $this->assertFalse(ConfigService::isKeyAllowed('EmailTransport.default.password'));
        $this->assertFalse(ConfigService::isKeyAllowed('EmailTransport.default.username'));

        // Debug mode should be blocked
        $this->assertFalse(ConfigService::isKeyAllowed('debug'));

        // Session config should be blocked
        $this->assertFalse(ConfigService::isKeyAllowed('Session.timeout'));
        $this->assertFalse(ConfigService::isKeyAllowed('Session.handler'));

        // Error config should be blocked
        $this->assertFalse(ConfigService::isKeyAllowed('Error.exceptionRenderer'));
    }

    /**
     * Test that allowed keys are accepted
     *
     * @return void
     */
    public function testAllowedKeysAreAccepted(): void
    {
        // App settings should be allowed
        $this->assertTrue(ConfigService::isKeyAllowed('App.defaultTimezone'));
        $this->assertTrue(ConfigService::isKeyAllowed('App.defaultLocale'));
        $this->assertTrue(ConfigService::isKeyAllowed('App.fullBaseUrl'));

        // Mail settings (non-credentials) should be allowed
        $this->assertTrue(ConfigService::isKeyAllowed('Mail.default.from'));
        $this->assertTrue(ConfigService::isKeyAllowed('Mail.default.fromName'));

        // Email transport non-credential settings should be allowed
        $this->assertTrue(ConfigService::isKeyAllowed('EmailTransport.default.host'));
        $this->assertTrue(ConfigService::isKeyAllowed('EmailTransport.default.port'));
        $this->assertTrue(ConfigService::isKeyAllowed('EmailTransport.default.tls'));
        $this->assertTrue(ConfigService::isKeyAllowed('EmailTransport.default.timeout'));

        // Cache settings should be allowed
        $this->assertTrue(ConfigService::isKeyAllowed('Cache.default.duration'));

        // Custom settings should be allowed
        $this->assertTrue(ConfigService::isKeyAllowed('Custom.anything'));
    }

    /**
     * Test that unknown/unlisted keys are rejected (default deny)
     *
     * @return void
     */
    public function testUnknownKeysAreRejected(): void
    {
        // Keys not in allowlist should be rejected
        $this->assertFalse(ConfigService::isKeyAllowed('Unknown.setting'));
        $this->assertFalse(ConfigService::isKeyAllowed('RandomKey'));
        $this->assertFalse(ConfigService::isKeyAllowed(''));
    }

    /**
     * Test that getBlockedKeyPrefixes returns expected values
     *
     * @return void
     */
    public function testGetBlockedKeyPrefixes(): void
    {
        $blocked = ConfigService::getBlockedKeyPrefixes();

        $this->assertIsArray($blocked);
        $this->assertContains('Security.', $blocked);
        $this->assertContains('Datasources.', $blocked);
        $this->assertContains('debug', $blocked);
    }

    /**
     * Test that getAllowedKeyPrefixes returns expected values
     *
     * @return void
     */
    public function testGetAllowedKeyPrefixes(): void
    {
        $allowed = ConfigService::getAllowedKeyPrefixes();

        $this->assertIsArray($allowed);
        $this->assertContains('App.', $allowed);
        $this->assertContains('Mail.', $allowed);
        $this->assertContains('Custom.', $allowed);
    }

    /**
     * Test castValue method
     *
     * @return void
     */
    public function testCastValue(): void
    {
        // Integer casting
        $this->assertSame(42, ConfigService::castValue('42', 'integer'));
        $this->assertSame(42, ConfigService::castValue('42', 'int'));

        // Float casting
        $this->assertSame(3.14, ConfigService::castValue('3.14', 'float'));

        // Boolean casting
        $this->assertTrue(ConfigService::castValue('1', 'boolean'));
        $this->assertTrue(ConfigService::castValue('true', 'bool'));
        $this->assertFalse(ConfigService::castValue('0', 'boolean'));
        $this->assertFalse(ConfigService::castValue('false', 'bool'));

        // JSON casting
        $this->assertSame(['key' => 'value'], ConfigService::castValue('{"key":"value"}', 'json'));

        // String (default)
        $this->assertSame('hello', ConfigService::castValue('hello', 'string'));
        $this->assertSame('hello', ConfigService::castValue('hello', 'unknown'));
    }

    /**
     * Test getEncryptionKey reads from Configure
     *
     * @return void
     */
    public function testGetEncryptionKeyReadsFromConfigure(): void
    {
        $customKey = str_repeat('x', 64);
        Configure::write('Settings.encryptionKey', $customKey);

        $this->assertSame($customKey, ConfigService::getEncryptionKey());
    }

    /**
     * Test getEncryptionKey throws when not set
     *
     * @return void
     */
    public function testGetEncryptionKeyThrowsWhenNotSet(): void
    {
        Configure::delete('Settings.encryptionKey');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Settings.encryptionKey is not configured');
        ConfigService::getEncryptionKey();
    }

    /**
     * Test getEncryptionKey throws when empty string
     *
     * @return void
     */
    public function testGetEncryptionKeyThrowsOnEmptyString(): void
    {
        Configure::write('Settings.encryptionKey', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Settings.encryptionKey is not configured');
        ConfigService::getEncryptionKey();
    }

    /**
     * Test encrypt/decrypt round-trip
     *
     * @return void
     */
    public function testEncryptDecryptRoundTrip(): void
    {
        $plaintext = 'my-secret-api-key-12345';

        $encrypted = ConfigService::encryptValue($plaintext);

        // Encrypted value should be different from plaintext
        $this->assertNotSame($plaintext, $encrypted);

        // Should be valid base64
        $this->assertNotFalse(base64_decode($encrypted, true));

        // Decrypt should return original plaintext
        $decrypted = ConfigService::decryptValue($encrypted);
        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test encrypt produces different output each time (due to IV)
     *
     * @return void
     */
    public function testEncryptProducesDifferentOutput(): void
    {
        $plaintext = 'same-value';

        $encrypted1 = ConfigService::encryptValue($plaintext);
        $encrypted2 = ConfigService::encryptValue($plaintext);

        $this->assertNotSame($encrypted1, $encrypted2);

        // Both should decrypt to the same value
        $this->assertSame($plaintext, ConfigService::decryptValue($encrypted1));
        $this->assertSame($plaintext, ConfigService::decryptValue($encrypted2));
    }

    /**
     * Test decryptValue returns null for invalid data
     *
     * @return void
     */
    public function testDecryptValueReturnsNullForInvalidData(): void
    {
        $this->assertNull(ConfigService::decryptValue(''));
        $this->assertNull(ConfigService::decryptValue('not-valid-base64!!!'));
        $this->assertNull(ConfigService::decryptValue(base64_encode('garbage-data')));
    }

    /**
     * Test decryptValue returns null when key has changed
     *
     * @return void
     */
    public function testDecryptValueReturnsNullForWrongKey(): void
    {
        // Encrypt with current key
        $plaintext = 'secret-value';
        $encrypted = ConfigService::encryptValue($plaintext);

        // Change the key
        Configure::write('Settings.encryptionKey', str_repeat('z', 64));

        // Decrypt should fail gracefully
        $this->assertNull(ConfigService::decryptValue($encrypted));
    }

    /**
     * Test castValue for encrypted type returns value as-is
     *
     * @return void
     */
    public function testCastValueEncryptedReturnsAsIs(): void
    {
        $this->assertSame('already-decrypted', ConfigService::castValue('already-decrypted', 'encrypted'));
    }

    /**
     * Test reload decrypts encrypted values into Configure
     *
     * @return void
     */
    public function testReloadDecryptsEncryptedValues(): void
    {
        $table = TableRegistry::getTableLocator()->get('DbConfig.AppSettings');

        // Insert an encrypted value via direct save (beforeSave will encrypt it)
        $plaintext = 'my-api-key-value';
        $entity = $table->newEntity([
            'module' => 'Custom',
            'config_key' => 'Custom.test_secret',
            'value' => $plaintext,
            'type' => 'encrypted',
        ]);
        $saved = $table->save($entity);
        $this->assertNotFalse($saved);

        // Verify stored value is encrypted (not plaintext)
        $stored = $table->get($saved->id);
        $this->assertNotSame($plaintext, $stored->value);

        // Clear Configure to ensure reload populates it
        Configure::delete('Custom.test_secret');

        // Reload should decrypt and write plaintext to Configure
        ConfigService::reload();
        $this->assertSame($plaintext, Configure::read('Custom.test_secret'));
    }
}
