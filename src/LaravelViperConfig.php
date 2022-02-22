<?php

namespace RlWebdiensten\LaravelViper;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RlWebdiensten\LaravelViper\Contracts\ViperConfig;
use stdClass;

class LaravelViperConfig implements ViperConfig
{
    protected ?string $apiKey;
    protected ?string $jwtToken;
    protected ?int $jwtExpires;
    protected ?string $refreshToken;

    public function __construct(?string $apiKey = null, ?string $jwtToken = null, ?int $jwtExpires = null, ?string $refreshToken = null)
    {
        $config = $this->loadConfig();

        $this->apiKey = $apiKey;
        $this->jwtToken = $jwtToken ?? $config->jwt ?? null;
        $this->jwtExpires = $jwtExpires ?? $config->expires ?? null;
        $this->refreshToken = $refreshToken ?? $config->refresh_token ?? null;
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
     * @param int|null $jwtExpires
     */
    public function setJwtExpires(?int $jwtExpires): void
    {
        $this->jwtExpires = $jwtExpires;
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * @return int|null
     */
    public function getJwtExpires(): ?int
    {
        return $this->jwtExpires;
    }

    /**
     * @return string|null
     */
    public function getJwtToken(): ?string
    {
        return $this->jwtToken;
    }

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
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
        if (! $this->jwtExpires) {
            return Carbon::now();
        }

        return Carbon::createFromTimestamp($this->jwtExpires);
    }

    public function saveConfig(): void
    {
        $config = $this->loadConfig();

        $config->refresh_token = $this->refreshToken;
        $config->jwt = $this->jwtToken;
        $config->expires = $this->jwtExpires;

        Storage::put('viper.api.json', (string) json_encode($config));
    }

    private function loadConfig(): stdClass
    {
        $config = '{}';

        if (Storage::exists('viper.api.json')) {
            $config = Storage::get(
                'viper.api.json',
            );
        }

        return (object) json_decode($config ?? '{}', false);
    }
}
