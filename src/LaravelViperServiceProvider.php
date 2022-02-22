<?php

namespace RlWebdiensten\LaravelViper;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use RlWebdiensten\LaravelViper\Commands\ViperLogin;
use RlWebdiensten\LaravelViper\Commands\ViperRefresh;
use RlWebdiensten\LaravelViper\Contracts\ViperConfig;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelViperServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-viper')
            ->hasConfigFile("viper")
            ->hasCommand(ViperLogin::class)
            ->hasCommand(ViperRefresh::class);

        $this->app->alias(LaravelViper::class, 'laravel-viper');

        $this->app->when(LaravelViper::class)
            ->needs(ClientInterface::class)
            ->give(Client::class);

        $this->app->singleton(ViperConfig::class, function () {
            return new LaravelViperConfig(config('viper.api_token'));
        });
    }
}
