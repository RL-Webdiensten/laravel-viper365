<?php

namespace RlWebdiensten\LaravelViper;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RlWebdiensten\LaravelViper\Contracts\ViperConfig;

class LaravelViperConfig implements ViperConfig
{
    protected string $apiKey;
    protected ?string $jwtToken;
    protected ?int $jwtExpires;
    protected ?string $refreshToken;

    function __construct($apiKey, $jwtToken = null, $jwtExpires = null, $refreshToken = null)
    {
        $this->apiKey = $apiKey;
        $config = $this->loadConfig();

        $this->jwtToken = $jwtToken ?? $config->jwt;
        $this->jwtExpires = $jwtExpires ?? $config->expires;
        $this->refreshToken = $refreshToken ?? $config->refresh_token;
    }

    private function loadConfig(): object
    {
        $config = '{}';

        if (Storage::exists('viper.api.json')) {
            $config = Storage::get(
                'viper.api.json',
            );
        }

        return (object) json_decode($config, false);
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string|null $jwtToken
     */
    public function setJwtToken(?string $jwtToken): void
    {
        $this->jwtToken = $jwtToken;
    }

    /**
     * @param string|null $refreshToken
     */
    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @param int|null $refreshExpires
     */
    public function setJwtExpires(?int $refreshExpires): void
    {
        $this->jwtExpires = $refreshExpires;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return int|mixed|null
     */
    public function getJwtExpires(): mixed
    {
        return $this->jwtExpires;
    }

    /**
     * @return mixed|string|null
     */
    public function getJwtToken(): mixed
    {
        return $this->jwtToken;
    }

    /**
     * @return mixed|string|null
     */
    public function getRefreshToken(): mixed
    {
        return $this->refreshToken;
    }

    public function isTokenValid(): bool
    {
        $diff = Carbon::now()->diffInMinutes($this->getTokenExpireDate());
        return $diff > 5;
    }

    public function getTokenExpireDate(): Carbon
    {
        if (!$this->jwtToken){
            return Carbon::now();
        }
        return Carbon::createFromTimestamp($this->jwtToken);
    }

    public function saveConfig(): void
    {
        $config = $this->loadConfig();
        $config->refresh_token = $this->refreshToken;
        $config->jwt = $this->jwtToken;
        $config->expires = $this->jwtExpires;
        Storage::put('viper.api.json', json_encode($config));
    }

}
