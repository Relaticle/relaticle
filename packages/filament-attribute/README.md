# This is my package filament-attribute

[![Latest Version on Packagist](https://img.shields.io/packagist/v/manukminasyan/filament-attribute.svg?style=flat-square)](https://packagist.org/packages/manukminasyan/filament-attribute)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/manukminasyan/filament-attribute/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/manukminasyan/filament-attribute/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/manukminasyan/filament-attribute/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/manukminasyan/filament-attribute/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/manukminasyan/filament-attribute.svg?style=flat-square)](https://packagist.org/packages/manukminasyan/filament-attribute)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require manukminasyan/filament-attribute
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-attribute-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-attribute-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-attribute-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentAttribute = new ManukMinasyan\FilamentAttribute();
echo $filamentAttribute->echoPhrase('Hello, ManukMinasyan!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [manukminasyan](https://github.com/manukminasyan)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
