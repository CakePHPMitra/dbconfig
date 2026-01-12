# DbConfig for CakePHP 5

[![CakePHP 5](https://img.shields.io/badge/CakePHP-5.x-red.svg)](https://cakephp.org)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Store and manage application configuration in database for CakePHP 5 applications.

## Features

- Store configuration in database
- Auto-load configuration on bootstrap
- Type casting (string, int, float, bool, json)
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
