# Architecture

## Overview

DbConfig uses a simple service-based architecture with minimal components.

```
DbConfig/
├── src/
│   ├── DbConfigPlugin.php        # Plugin bootstrap
│   ├── Controller/
│   │   ├── AppController.php     # Base controller
│   │   └── AppSettingsController.php  # Settings management
│   ├── Model/
│   │   ├── Entity/
│   │   │   └── AppSetting.php    # Setting entity
│   │   └── Table/
│   │       └── AppSettingsTable.php  # Settings table
│   ├── Service/
│   │   └── ConfigService.php     # Configuration loading
│   └── Command/
│       └── PublishCommand.php    # Template publishing
├── config/
│   ├── Migrations/               # Database migrations
│   └── Seeds/                    # Data seeds
└── templates/
    └── AppSettings/
        └── index.php             # Settings dashboard
```

## Components

### DbConfigPlugin

The main plugin class that:
- Loads configuration from database on bootstrap
- Registers routes for the dashboard
- Handles graceful failures if database is unavailable

### ConfigService

Static service class that:
- Loads all settings from database
- Casts values to appropriate types
- Applies system configuration (timezone, encoding, etc.)

### AppSettingsTable

CakePHP Table class for the `app_settings` table.

### PublishCommand

CLI command to publish templates to the application for customization.

## Bootstrap Flow

1. Application loads DbConfig plugin
2. `DbConfigPlugin::bootstrap()` is called
3. `ConfigService::reload()` loads settings from database
4. Values are cast to appropriate types
5. System settings (timezone, encoding) are applied
6. Configuration available via `Configure::read()`

## Error Handling

If the database is unavailable during bootstrap, the plugin logs a warning and continues with file-based configuration only.
