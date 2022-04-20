<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use RlWebdiensten\LaravelViper\Contracts\ViperConfig;
use RlWebdiensten\LaravelViper\Exceptions\InvalidResponseException;
use RlWebdiensten\LaravelViper\Exceptions\RequestInvalidException;
use RlWebdiensten\LaravelViper\LaravelViper;

it('can authenticate the user', function () {
    $guzzleMock = Mockery::mock(Client::class);
    $guzzleMock
        ->shouldReceive('request')
        ->andReturn(new Response(200, [], '{
            "UserName": "email@example.com",
            "Jwt": "jwttoken",
            "ExpiresIn": 1800,
            "RefreshToken": "refreshtoken"
        }'));

    app()->instance(Client::class, $guzzleMock);
    app()->bind(LaravelViper::class, function () use ($guzzleMock) {
        return new LaravelViper(app(ViperConfig::class), $guzzleMock);
    });

    Config::set('viper.api_token', 'TEST');

    expect(RlWebdiensten\LaravelViper\Facades\LaravelViper::authenticateUser('test', 'test'))->toBeTrue();
});

it('it throws when we get an 400 error', function () {
    $guzzleMock = Mockery::mock(Client::class);
    $guzzleMock
        ->shouldReceive('request')
        ->andReturn(new Response(400, [], ''));

    app()->instance(Client::class, $guzzleMock);
    app()->bind(LaravelViper::class, function () use ($guzzleMock) {
        return new LaravelViper(app(ViperConfig::class), $guzzleMock);
    });

    Config::set('viper.api_token', 'TEST');

    RlWebdiensten\LaravelViper\Facades\LaravelViper::authenticateUser('test', 'test');
})->throws(RequestInvalidException::class);

it('it throws exception when we get an empty body', function () {
    $guzzleMock = Mockery::mock(Client::class);
    $guzzleMock
        ->shouldReceive('request')
        ->andReturn(new Response(200, [], ''));

    app()->instance(Client::class, $guzzleMock);
    app()->bind(LaravelViper::class, function () use ($guzzleMock) {
        return new LaravelViper(app(ViperConfig::class), $guzzleMock);
    });

    Config::set('viper.api_token', 'TEST');

    RlWebdiensten\LaravelViper\Facades\LaravelViper::authenticateUser('test', 'test');
})->throws(InvalidResponseException::class);

it('expects that config is updated on authenticate user', function () {
    $viperConfig = Mockery::mock(ViperConfig::class);
    $viperConfig->shouldReceive('getApiKey')->andReturn('test')->once();
    $viperConfig->shouldReceive('setRefreshToken')->withArgs(['refreshtoken'])->once();
    $viperConfig->shouldReceive('setJwtToken')->withArgs(['jwttoken'])->once();
    $viperConfig->shouldReceive('setJwtExpires')->withArgs(function ($time) {
        $expectedTime = strtotime("+1800 seconds");

        return $time >= $expectedTime && $time <= ($expectedTime + 5);
    })->once();
    $viperConfig->shouldReceive('saveConfig')->once();

    $guzzleMock = Mockery::mock(Client::class);
    $guzzleMock
        ->shouldReceive('request')
        ->andReturn(new Response(200, [], '{
            "UserName": "email@example.com",
            "Jwt": "jwttoken",
            "ExpiresIn": 1800,
            "RefreshToken": "refreshtoken"
        }'));

    app()->instance(Client::class, $guzzleMock);
    app()->bind(LaravelViper::class, function () use ($viperConfig, $guzzleMock) {
        return new LaravelViper($viperConfig, $guzzleMock);
    });

    expect(RlWebdiensten\LaravelViper\Facades\LaravelViper::authenticateUser('test', 'test'))->toBeTrue();
});
