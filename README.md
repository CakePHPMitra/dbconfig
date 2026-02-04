# DbConfig for CakePHP 5

[![CakePHP 5](https://img.shields.io/badge/CakePHP-5.x-red.svg)](https://cakephp.org)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Store and manage application configuration in database for CakePHP 5 applications.

## Features

- Store configuration in database
- Auto-load configuration on bootstrap
- Type casting (string, int, float, bool, json, encrypted)
- Built-in encryption for sensitive values (API keys, passwords)
- CLI command for template publishing
- Seamless CakePHP Configure integration

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | >= 8.1 |
| CakePHP | ^5.0 |

**No additional dependencies required.**

## Installation

1. Add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/CakePHPMitra/dbconfig"
        }
    ]
}
```

2. Install via Composer:

```bash
composer require cakephpmitra/dbconfig:dev-main
```

3. Load the plugin:

```bash
bin/cake plugin load DbConfig
```

Or add to `src/Application.php` in the `bootstrap()` method:

```php
$this->addPlugin('DbConfig');
```

4. Run migrations:

```bash
bin/cake migrations migrate --plugin DbConfig
```

## How It Works

1. **Bootstrap**: Configuration loads automatically from database on application start
2. **Storage**: Settings stored in `app_settings` table with type information
3. **Access**: Use standard `Configure::read()` to access values
4. **Dashboard**: Web interface at `/db-config` for management

## Encryption

The plugin supports transparent encryption for sensitive values like API keys and passwords.

### Setup

Add an encryption key to your host application's `config/app_local.php`:

```php
'Settings' => [
    'encryptionKey' => env('SETTINGS_ENCRYPTION_KEY', ''),
],
```

Generate a key:

```bash
openssl rand -base64 48
```

Add it to your `.env`:

```
SETTINGS_ENCRYPTION_KEY=your-generated-key-here
```

### Usage

Store a setting with `type = 'encrypted'` in the `app_settings` table. The plugin handles encryption and decryption automatically:

```php
// Value is auto-decrypted when loaded into Configure
$apiKey = Configure::read('Custom.CloudPE.api_token'); // Returns plaintext
```

In the admin UI at `/db-config`, encrypted values display as `********` and use password fields for editing. Submitting an empty password field keeps the existing value.

### Best Practices

- **Set the encryption key** via `SETTINGS_ENCRYPTION_KEY` environment variable. The plugin requires `Settings.encryptionKey` to be configured.
- **Backup your encryption key.** If lost, all encrypted values become unreadable and must be re-entered manually. Store the key separately from database backups (e.g., in a password manager or secrets vault).
- **Use the same key across environments** that share database dumps (production, staging, local). Each environment can have a different `Security.salt` without affecting encrypted settings.
- **Key rotation** is not currently automated. To change the encryption key, decrypt all values with the old key and re-encrypt with the new key.

## Known Issues

### `Settings.encryptionKey` RuntimeException

If you use the `encrypted` type and have not configured `Settings.encryptionKey`, the application will throw a `RuntimeException`:

```
Settings.encryptionKey is not configured. Add it to your config/app_local.php:
'Settings' => ['encryptionKey' => env('SETTINGS_ENCRYPTION_KEY', '')]
```

**Fix**: Add the encryption key to your `config/app_local.php` and `.env` file as described in the [Encryption](#encryption) section above. If you are not using any `encrypted` type settings, this error will not occur.

## Documentation

See the [docs](docs/) folder for detailed documentation:

- [Features](docs/features/) - Feature documentation
- [Development](docs/development/) - Implementation details

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Issues

Report bugs and feature requests on the [Issue Tracker](https://github.com/CakePHPMitra/dbconfig/issues).

## Author

[Atul Mahankal](https://atulmahankal.github.io/atulmahankal/)

## License

MIT License - see [LICENSE](LICENSE) file.
