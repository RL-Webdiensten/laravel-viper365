# Viper365 API client wrapper for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rlwebdiensten/laravel-viper.svg?style=flat-square)](https://packagist.org/packages/rlwebdiensten/laravel-viper)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/RL-Webdiensten/laravel-viper365/run-tests?label=tests)](https://github.com/RL-Webdiensten/laravel-viper365/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/RL-Webdiensten/laravel-viper365/Check%20&%20fix%20styling?label=code%20style)](https://github.com/RL-Webdiensten/laravel-viper365/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rlwebdiensten/laravel-viper.svg?style=flat-square)](https://packagist.org/packages/rlwebdiensten/laravel-viper)

A simple Viper365 API client wrapper for Laravel.

## Installation

You can install the package via composer:

```bash
composer require rlwebdiensten/laravel-viper
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-viper-config"
```

This is the contents of the published config file:

```php
return [
    'api_token' => env('VIPER_API_TOKEN', ''),
];
```

## Usage

Using the constructor
```php
protected LaravelViper $viperService;

function __construct()
{
    $this->viperService = app()->make('RlWebdiensten\LaravelViper\LaravelViper');
}
```

Using dependency injection
```php
function __construct(\RlWebdiensten\LaravelViper\LaravelViper $viperService)
{
    $this->viperService = $viperService;
}
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

- [RL-Webdiensten](https://github.com/RL-Webdiensten)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
