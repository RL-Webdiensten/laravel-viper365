<?php

namespace RlWebdiensten\LaravelViper\Contracts;

interface ViperConfig
{
    public function getRefreshToken();
    public function getApiKey();
    public function getJwtToken();

    public function setRefreshToken(string $RefreshToken);
    public function setJwtToken(string $Jwt);
    public function setJwtExpires(int $getDateFromExpiry);
    public function saveConfig();

    public function isTokenValid(): bool;
}
