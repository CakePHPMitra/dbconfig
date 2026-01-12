# CLI Commands

## Overview

DbConfig provides CLI commands for plugin management.

## Publish Command

Publishes plugin templates to the application for customization.

### Usage

```bash
bin/cake dbconfig publish
```

### Options

| Option | Short | Description |
|--------|-------|-------------|
| `--overwrite` | `-o` | Overwrite existing files without prompting |

### Examples

```bash
# Interactive mode (prompts before overwriting)
bin/cake dbconfig publish

# Overwrite all files
bin/cake dbconfig publish --overwrite
```

### Published Files

Templates are copied to `templates/plugin/DbConfig/`:

- `AppSettings/index.php` - Settings dashboard template

### Customization

After publishing, edit the templates in your application's `templates/plugin/DbConfig/` directory. Published templates take precedence over plugin templates.

## Migration Commands

Standard CakePHP migration commands work with the plugin:

```bash
# Run migrations
bin/cake migrations migrate --plugin DbConfig

# Rollback migrations
bin/cake migrations rollback --plugin DbConfig

# Check migration status
bin/cake migrations status --plugin DbConfig
```

## Seed Commands

```bash
# Run seeds
bin/cake migrations seed --plugin DbConfig --seed AppSettingsSeed
```
