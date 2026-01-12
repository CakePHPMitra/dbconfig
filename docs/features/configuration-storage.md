# Configuration Storage

## Overview

DbConfig stores application configuration in the `app_settings` database table, making settings persistent and manageable through the database.

## Database Schema

| Column | Type | Description |
|--------|------|-------------|
| id | int | Primary key |
| module | string | Module/group name for organization |
| config_key | string | Full configuration key path |
| value | text | Configuration value |
| type | string | Value type for casting |
| options | text | Optional metadata (nullable) |

## Auto-Loading

Configuration is automatically loaded during plugin bootstrap:

```php
public function bootstrap(PluginApplicationInterface $app): void
{
    parent::bootstrap($app);

    try {
        \DbConfig\Service\ConfigService::reload();
    } catch (\Exception $e) {
        // Graceful fallback if database not available
    }
}
```

## Reading Configuration

Use standard CakePHP Configure methods:

```php
use Cake\Core\Configure;

// Read a single value
$siteName = Configure::read('App.siteName');

// Read with default
$debug = Configure::read('debug', false);

// Read entire section
$appConfig = Configure::read('App');
```

## Manual Reload

Force reload configuration from database:

```php
use DbConfig\Service\ConfigService;

ConfigService::reload();
```

## System Settings Applied

When configuration is loaded, the following system settings are automatically applied:

- Default timezone (`App.defaultTimezone`)
- MB string encoding (`App.encoding`)
- Intl default locale (`App.defaultLocale`)
- Full base URL for Router
