<?php

namespace RlWebdiensten\LaravelViper;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RlWebdiensten\LaravelViper\Contracts\ViperConfig;

class LaravelViper
{
    public function __construct(protected ViperConfig $config, protected Client $client)
    {
    }

    public function authenticateUser(string $username, string $password): bool
    {
        $result = $this->makeRequest("POST", "AuthenticateUser", [
            "Username" => $username,
            "Password" => $password,
        ]);

        if (! isset($result['RefreshToken'], $result['Jwt'], $result['ExpiresIn'])) {
            return false;
        }

        $this->config->setRefreshToken($result['RefreshToken']);
        $this->config->setJwtToken($result['Jwt']);
        $this->config->setJwtExpires($this->getTimeFromExpiry($result['ExpiresIn']));
        $this->config->saveConfig();

        return true;
    }

    public function refreshToken(): bool
    {
        if (is_null($this->config->getRefreshToken())) {
            return false;
        }

        $uri = "RefreshToken?token=" . urlencode($this->config->getRefreshToken());
        $result = $this->makeRequest("POST", $uri, [], true);
        if (! isset($result['Jwt'], $result['ExpiresIn'])) {
            return false;
        }

        $this->config->setJwtToken($result['Jwt']);
        $this->config->setJwtExpires($this->getTimeFromExpiry($result['ExpiresIn']));
        $this->config->saveConfig();

        return true;
    }

    public function getAllPersons(): array
    {
        return $this->makeRequestWithToken("GET", "Persons");
    }

    public function getSinglePerson(int $userId): array
    {
        $this->checkToken();

        return $this->makeRequestWithToken("GET", "Persons/$userId");
    }

    public function updatePerson(int $userId, array $userData): array
    {
        return $this->makeRequestWithToken("PATCH", "Persons/$userId", $userData);
    }

    public function createPerson(array $userData): array
    {
        return $this->makeRequestWithToken("POST", "Persons", $userData);
    }

    public function checkToken(): void
    {
        if (
            empty($this->config->getApiKey())
            || empty($this->config->getJwtToken())
            || empty($this->config->getRefreshToken())
        ) {
            return;
        }

        if (! $this->config->isTokenValid()) {
            $this->refreshToken();
        }
    }

    private function makeRequestWithToken(string $method, string $uri, ?array $body = null): array
    {
        $this->checkToken();

        return $this->makeRequest($method, $uri, $body, true);
    }

    private function makeRequest(string $method, string $uri, ?array $body = null, bool $includeJwt = false): array
    {
        try {
            $response = $this->client->request($method, $uri, array_merge($this->getClientOptions($includeJwt), $this->getJsonBody($body)));
            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $result = $this->convertIncomingResponseToArray($response);
            if (! $result) {
                return [];
            }

            return $result;
        } catch (GuzzleException) {
            return [];
        }
    }

    private function getClientOptions(bool $includeJwt = false): array
    {
        $options = [
            'base_uri' => 'https://' . config('viper.api_endpoint') . '/v2/',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization-ApiKey' => $this->config->getApiKey(),
            ],
            'http_errors' => false,
            'debug' => false,
        ];
        if ($includeJwt) {
            $options['headers']['Authorization-JWT'] = $this->config->getJwtToken();
        }

        return $options;
    }

    private function convertIncomingResponseToArray(ResponseInterface $response): ?array
    {
        try {
            $response->getBody()->rewind();
            $body = $response->getBody()->getContents();

            return (array) json_decode($body, true, 10, JSON_THROW_ON_ERROR);
        } catch (Exception) {
            return null;
        }
    }

    private function getTimeFromExpiry(int $expiresIn): int
    {
        $time = strtotime("+$expiresIn seconds");
        if ($time === false) {
            return time();
        }

        return $time;
    }

    private function getJsonBody(?array $body = null): array
    {
        if (is_null($body)) {
            return [];
        }

        return ['json' => $body];
    }
}
