<?php

namespace RlWebdiensten\LaravelViper;

use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RlWebdiensten\LaravelViper\Contracts\ViperConfig;

class LaravelViper
{
    public function __construct(protected ViperConfig $config, protected ClientInterface $client)
    {
    }

    public function authenticateUser($username, $password): bool
    {
        $result = $this->makePostRequest("AuthenticateUser", [
            "Username" => $username,
            "Password" => $password,
        ]);
        if (! isset($result['RefreshToken'], $result['Jwt'], $result['ExpiresIn'])) {
            return false;
        }

        $this->config->setRefreshToken($result['RefreshToken']);
        $this->config->setJwtToken($result['Jwt']);
        $this->config->setJwtExpires($this->getDateFromExpiry($result['ExpiresIn']));
        $this->config->saveConfig();

        return true;
    }

    public function refreshToken(): bool
    {
        $uri = "RefreshToken?token=".urlencode($this->config->getRefreshToken());
        $result = $this->makePostRequest($uri, [], true);
        if (! isset($result['Jwt'], $result['ExpiresIn'])) {
            return false;
        }

        $this->config->setJwtToken($result['Jwt']);
        $this->config->setJwtExpires($this->getDateFromExpiry($result['ExpiresIn']));
        $this->config->saveConfig();

        return true;
    }

    public function getAllPersons(): array
    {
        $this->checkToken();

        return $this->makeGetRequest("Persons", true);
    }

    public function getSinglePerson(int $userId): array
    {
        $this->checkToken();

        return $this->makeGetRequest("Persons/$userId", true);
    }

    public function updatePerson(int $userId, array $userData): array
    {
        $this->checkToken();

        return $this->makePatchRequest("Persons/$userId", $userData, true);
    }

    public function createPerson(array $userData): array
    {
        $this->checkToken();

        return $this->makePostRequest("Persons", $userData, true);
    }

    private function getClientOptions(bool $includeJwt = false): array
    {
        $options = [
            'base_uri' => 'https://basic-api.viper365.net/v2/',
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

    public function makeGetRequest(string $uri, bool $includeJwt = false): array
    {
        try {
            $response = $this->client->get($uri, $this->getClientOptions($includeJwt));
            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $result = self::convertIncomingResponseToArray($response);
            if (! $result) {
                return [];
            }

            return $result;
        } catch (GuzzleException) {
            return [];
        }
    }

    public function makePostRequest(string $uri, array $jsonBody, bool $includeJwt = false): array
    {
        try {
            $response = $this->client->post($uri, array_merge($this->getClientOptions($includeJwt), ['json' => $jsonBody]));
            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $result = self::convertIncomingResponseToArray($response);
            if (! $result) {
                return [];
            }

            return $result;
        } catch (GuzzleException) {
            return [];
        }
    }

    public function makePatchRequest(string $uri, array $jsonBody, bool $includeJwt = false): array
    {
        try {
            $response = $this->client->patch($uri, array_merge($this->getClientOptions($includeJwt), ['json' => $jsonBody]));
            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $result = self::convertIncomingResponseToArray($response);
            if (! $result) {
                return [];
            }

            return $result;
        } catch (GuzzleException) {
            return [];
        }
    }

    private static function convertIncomingResponseToArray(ResponseInterface $response): ?array
    {
        try {
            $response->getBody()->rewind();
            $body = $response->getBody()->getContents();

            return json_decode($body, true, 10, JSON_THROW_ON_ERROR);
        } catch (Exception) {
            return null;
        }
    }

    private function getDateFromExpiry(int $expiresIn): int
    {
        return strtotime("+$expiresIn seconds");
    }

    public function checkToken()
    {
        if (! $this->config->isTokenValid()) {
            $this->refreshToken();
        }
    }
}
