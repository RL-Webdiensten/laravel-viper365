<?php

namespace RlWebdiensten\LaravelViper\Contracts;

interface ViperConfig
{
    public function getRefreshToken(): ?string;

    public function getApiKey(): ?string;

    public function getJwtToken(): ?string;

    public function setRefreshToken(?string $refreshToken): void;

    public function setJwtToken(string $jwtToken): void;

    public function setJwtExpires(int $getDateFromExpiry): void;

    public function saveConfig(): void;

    public function isTokenValid(): bool;
}
