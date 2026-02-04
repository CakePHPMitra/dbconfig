# Encryption

The DbConfig plugin supports transparent encryption for sensitive configuration values such as API keys, passwords, and webhook URLs.

## How It Works

1. **Save**: When a setting with `type = 'encrypted'` is saved, `AppSettingsTable::beforeSave()` automatically encrypts the value using `Security::encrypt()` + base64 encoding before storing it in the database.
2. **Load**: When `ConfigService::reload()` runs (on every bootstrap), encrypted values are automatically decrypted and written to `Configure` as plaintext.
3. **Read**: Application code uses `Configure::read('Custom.key')` and receives the decrypted plaintext. No special handling needed.

```
Save: plaintext -> Security::encrypt() -> base64_encode() -> database
Load: database -> base64_decode() -> Security::decrypt() -> Configure
Read: Configure::read() -> plaintext
```

## Configuration

The plugin uses `Settings.encryptionKey` from CakePHP's `Configure`. Set it in your host application:

```php
// config/app_local.php
'Settings' => [
    'encryptionKey' => env('SETTINGS_ENCRYPTION_KEY', ''),
],
```

Generate a secure key:

```bash
openssl rand -base64 48
```

Add to your `.env` file:

```
SETTINGS_ENCRYPTION_KEY=your-generated-key-here
```

### Required Key

`Settings.encryptionKey` is **required** when using the `encrypted` type. If not set or empty, the plugin throws a `RuntimeException`. This ensures encrypted values are portable across environments that share database dumps.

## Creating Encrypted Settings

### Via Migration or Seed

```php
$this->table('app_settings')->insert([
    'module' => 'CloudPE',
    'config_key' => 'Custom.CloudPE.api_token',
    'value' => '', // Will be set via admin UI
    'type' => 'encrypted',
])->save();
```

### Via Admin UI

Navigate to `/db-config` and create a new setting with type `encrypted`. The value field renders as a password input.

### Via Code

```php
$table = $this->fetchTable('DbConfig.AppSettings');
$entity = $table->newEntity([
    'module' => 'CloudPE',
    'config_key' => 'Custom.CloudPE.api_token',
    'value' => 'your-api-token', // Will be auto-encrypted on save
    'type' => 'encrypted',
]);
$table->save($entity);
```

## Admin UI Behavior

- **Display**: Encrypted values show as `********` in the settings list. The actual ciphertext is never rendered to the browser.
- **Edit**: A password input field is shown. The placeholder reads "Enter new value or leave empty to keep existing".
- **Empty submit**: Submitting an empty password field keeps the existing encrypted value unchanged.

## Error Handling

When decryption fails (wrong key, corrupted data, missing key):

- The setting is **skipped** during `ConfigService::reload()` — the app does not crash.
- A warning is logged: `[DbConfig] Failed to decrypt setting 'key'. Skipping. Check encryption key configuration.`
- `Configure::read('key')` returns `null` for that setting.

This graceful failure means your application stays functional even if the encryption key changes. Integrations that depend on encrypted settings will show as "not configured" rather than causing errors.

## Multi-Environment Setup

For environments that share database dumps (e.g., importing production DB into staging):

```
# Production .env
SECURITY_SALT=prod-unique-salt
SETTINGS_ENCRYPTION_KEY=shared-encryption-key

# Staging .env (after importing prod DB)
SECURITY_SALT=staging-unique-salt          # Different - OK
SETTINGS_ENCRYPTION_KEY=shared-encryption-key  # Same - encrypted values work

# Local .env (after importing prod DB)
SECURITY_SALT=local-unique-salt            # Different - OK
SETTINGS_ENCRYPTION_KEY=shared-encryption-key  # Same - encrypted values work
```

## Security Considerations

- Encryption uses CakePHP's `Security::encrypt()` which implements AES-256 with HMAC integrity checking.
- Encrypted values are base64-encoded for safe storage in TEXT database columns.
- The encryption key should be at least 32 characters long.
- Never commit the encryption key to version control. Use environment variables.
- The admin UI never sends encrypted values to the browser — neither as plaintext nor as ciphertext.
- If the encryption key is lost, encrypted values cannot be recovered. They must be re-entered manually (the credentials exist in the external services they came from).
