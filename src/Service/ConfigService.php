<?php

declare(strict_types=1);

namespace DbConfig\Service;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Security;

class ConfigService
{
    /**
     * SECURITY: Config keys that cannot be modified via database settings.
     * These are security-sensitive configurations that should only be set via environment/.env
     *
     * @var array<string>
     */
    protected static array $blockedKeyPrefixes = [
        'Security.',           // Security salt, encryption keys
        'Datasources.',        // Database credentials
        'EmailTransport.default.password',  // SMTP password
        'EmailTransport.default.username',  // SMTP username (can contain secrets)
        'debug',               // Debug mode
        'Error.',              // Error handling config
        'Session.',            // Session configuration
    ];

    /**
     * SECURITY: Allowed config key prefixes (allowlist approach).
     * Only keys starting with these prefixes can be stored in database.
     *
     * @var array<string>
     */
    protected static array $allowedKeyPrefixes = [
        'App.',
        'Mail.',
        'EmailTransport.default.host',
        'EmailTransport.default.port',
        'EmailTransport.default.tls',
        'EmailTransport.default.timeout',
        'Cache.',
        'Log.',
        'Asset.',
        'Custom.',
    ];

    /**
     * Check if a config key is allowed to be stored/modified via database.
     *
     * @param string $key Config key to validate
     * @return bool True if allowed, false if blocked
     */
    public static function isKeyAllowed(string $key): bool
    {
        // Check blocklist first (explicit deny)
        foreach (static::$blockedKeyPrefixes as $blocked) {
            if (str_starts_with($key, $blocked) || $key === $blocked) {
                return false;
            }
        }

        // Check allowlist (explicit allow)
        foreach (static::$allowedKeyPrefixes as $allowed) {
            if (str_starts_with($key, $allowed)) {
                return true;
            }
        }

        // Default deny for unlisted keys
        return false;
    }

    /**
     * Get list of blocked key prefixes (for documentation/UI)
     *
     * @return array<string>
     */
    public static function getBlockedKeyPrefixes(): array
    {
        return static::$blockedKeyPrefixes;
    }

    /**
     * Get list of allowed key prefixes (for documentation/UI)
     *
     * @return array<string>
     */
    public static function getAllowedKeyPrefixes(): array
    {
        return static::$allowedKeyPrefixes;
    }

    /**
     * Get the encryption key for encrypting/decrypting sensitive values.
     *
     * Reads `Settings.encryptionKey` from Configure. This key is required
     * and must be set in the host application's configuration.
     *
     * @return string The encryption key
     * @throws \RuntimeException If Settings.encryptionKey is not configured or empty
     */
    public static function getEncryptionKey(): string
    {
        $key = Configure::read('Settings.encryptionKey');

        if ($key !== null && $key !== '') {
            return (string)$key;
        }

        throw new \RuntimeException(
            'Settings.encryptionKey is not configured. '
            . 'Add it to your config/app_local.php: '
            . "'Settings' => ['encryptionKey' => env('SETTINGS_ENCRYPTION_KEY', '')]"
        );
    }

    /**
     * Encrypt a plaintext value for database storage.
     *
     * Uses `Security::encrypt()` with base64 encoding for safe storage in TEXT columns.
     *
     * @param string $value Plaintext value to encrypt
     * @return string Base64-encoded encrypted value
     */
    public static function encryptValue(string $value): string
    {
        $encrypted = Security::encrypt($value, static::getEncryptionKey());

        return base64_encode($encrypted);
    }

    /**
     * Decrypt an encrypted value from database storage.
     *
     * Returns null on failure (wrong key, corrupted data) rather than throwing.
     *
     * @param string $encryptedValue Base64-encoded encrypted value
     * @return string|null Decrypted plaintext, or null on failure
     */
    public static function decryptValue(string $encryptedValue): ?string
    {
        if ($encryptedValue === '') {
            return null;
        }

        $decoded = base64_decode($encryptedValue, true);
        if ($decoded === false) {
            return null;
        }

        try {
            $decrypted = Security::decrypt($decoded, static::getEncryptionKey());
        } catch (\Exception $e) {
            return null;
        }

        return $decrypted;
    }

    public static function reload(): void
    {
        $AppSettings = TableRegistry::getTableLocator()->get('DbConfig.AppSettings');
        $settings = $AppSettings->find()->select(['config_key', 'value', 'type'])->all();

        foreach ($settings as $setting) {
            // SECURITY: Skip blocked keys that may have been inserted manually
            if (!static::isKeyAllowed($setting->config_key)) {
                continue;
            }

            $value = $setting->value;

            // Decrypt encrypted values before writing to Configure
            if (strtolower($setting->type) === 'encrypted') {
                $decrypted = static::decryptValue($value);
                if ($decrypted === null) {
                    Log::warning(
                        "[DbConfig] Failed to decrypt setting '{$setting->config_key}'. "
                        . 'Skipping. Check encryption key configuration.'
                    );
                    continue;
                }
                $value = $decrypted;
            }

            Configure::write($setting->config_key, self::castValue($value, $setting->type));
        }

        static::apply();
    }

    public static function castValue($value, $type)
    {
        return match (strtolower($type)) {
            'int', 'integer' => (int)$value,
            'float' => (float)$value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'encrypted' => $value, // Already decrypted by reload(), treat as string
            default => $value,
        };
    }

    public static function apply(): void
    {
        /*
        * When debug = true the metadata cache should only last for a short time.
        */
        if (Configure::read('debug')) {
            Configure::write('Cache._cake_model_.duration', '+2 minutes');
            Configure::write('Cache._cake_translations_.duration', '+2 minutes');
        }

        /*
        * Set the default server timezone. Using UTC makes time calculations / conversions easier.
        * Check https://php.net/manual/en/timezones.php for list of valid timezone strings.
        */
        date_default_timezone_set(Configure::read('App.defaultTimezone'));

        /*
        * Configure the mbstring extension to use the correct encoding.
        */
        mb_internal_encoding(Configure::read('App.encoding'));

        /*
        * Set the default locale. This controls how dates, number and currency is
        * formatted and sets the default language to use for translations.
        */
        ini_set('intl.default_locale', Configure::read('App.defaultLocale'));

        /*
        * Set the full base URL.
        * This URL is used as the base of all absolute links.
        * Can be very useful for CLI/Commandline applications.
        */
        $fullBaseUrl = Configure::read('App.fullBaseUrl');
        if (!$fullBaseUrl) {
            /*
            * When using proxies or load balancers, SSL/TLS connections might
            * get terminated before reaching the server. If you trust the proxy,
            * you can enable `$trustProxy` to rely on the `X-Forwarded-Proto`
            * header to determine whether to generate URLs using `https`.
            *
            * See also https://book.cakephp.org/5/en/controllers/request-response.html#trusting-proxy-headers
            */
            $trustProxy = false;

            $s = null;
            if (env('HTTPS') || ($trustProxy && env('HTTP_X_FORWARDED_PROTO') === 'https')) {
                $s = 's';
            }

            $httpHost = env('HTTP_HOST');
            if ($httpHost) {
                $fullBaseUrl = 'http' . $s . '://' . $httpHost;
            }
            unset($httpHost, $s);
        }
        if ($fullBaseUrl) {
            Router::fullBaseUrl($fullBaseUrl);
        }
    }
}
