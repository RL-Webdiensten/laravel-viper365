<?php

namespace RlWebdiensten\LaravelViper;

use RlWebdiensten\LaravelViper\Commands\ViperLogin;
use RlWebdiensten\LaravelViper\Commands\ViperRefresh;
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
            ->hasConfigFile()
            ->hasCommand(ViperLogin::class)
            ->hasCommand(ViperRefresh::class);

        $this->app->singleton(LaravelViper::class, function (){
            $config = new LaravelViperConfig(config('viper.api_token'));
            return new LaravelViper($config);
        });

    }
}
