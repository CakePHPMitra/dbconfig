<?php
declare(strict_types=1);

namespace DbConfig\Test\TestCase\Service;

use Cake\TestSuite\TestCase;
use DbConfig\Service\ConfigService;

/**
 * ConfigService Test Case
 *
 * Tests for the ConfigService class, particularly security-related key validation.
 */
class ConfigServiceTest extends TestCase
{
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
}
