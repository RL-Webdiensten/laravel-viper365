<?php

use GuzzleHttp\Client;
use RlWebdiensten\LaravelViper\Contracts\ViperConfig;
use RlWebdiensten\LaravelViper\LaravelViper;

it('registers facade', function() {
    expect(app()->make('RlWebdiensten\LaravelViper\LaravelViper'))->toBeInstanceOf(LaravelViper::class);
    expect(app(LaravelViper::class))->toBeInstanceOf(LaravelViper::class);
    expect(app('laravel-viper'))->toBeInstanceOf(LaravelViper::class);
});

it('can check if token is valid', function() {
    $mock = Mockery::mock(ViperConfig::class);
    $mock->shouldReceive('isTokenValid')
        ->once()
        ->andReturn(true);

    $service = new LaravelViper($mock, new Client());
    $service->checkToken();
});

it('can check if token is valid facade', function() {
    $mock = Mockery::mock(ViperConfig::class);
    $mock->shouldReceive('isTokenValid')
        ->times(3)
        ->andReturn(true);

    app()->instance(ViperConfig::class, $mock);

    RlWebdiensten\LaravelViper\Facades\LaravelViper::checkToken();
    app(LaravelViper::class)->checkToken();
    app('laravel-viper')->checkToken();
});
