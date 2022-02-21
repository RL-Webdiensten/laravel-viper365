<?php

namespace RlWebdiensten\LaravelViper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RlWebdiensten\LaravelViper\LaravelViper
 */
class LaravelViper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-viper';
    }
}
