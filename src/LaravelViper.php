<?php

namespace RlWebdiensten\LaravelViper;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RlWebdiensten\LaravelViper\Contracts\ViperConfig;
use RlWebdiensten\LaravelViper\Exceptions\ConnectionFailedException;
use RlWebdiensten\LaravelViper\Exceptions\InvalidApiKeyException;
use RlWebdiensten\LaravelViper\Exceptions\InvalidResponseException;
use RlWebdiensten\LaravelViper\Exceptions\InvalidTokenException;
use RlWebdiensten\LaravelViper\Exceptions\RateLimitException;
use RlWebdiensten\LaravelViper\Exceptions\RequestInvalidException;
use RlWebdiensten\LaravelViper\Exceptions\ServerErrorException;

class LaravelViper
{
    public function __construct(protected ViperConfig $config, protected Client $client)
    {
    }

    /**
     * @throws ConnectionFailedException
     * @throws ServerErrorException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws InvalidApiKeyException
     * @throws RequestInvalidException
     */
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

    /**
     * @throws ServerErrorException
     * @throws ConnectionFailedException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws InvalidApiKeyException
     * @throws RequestInvalidException
     */
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

    /**
     * @throws ServerErrorException
     * @throws ConnectionFailedException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws InvalidApiKeyException
     * @throws RequestInvalidException
     */
    public function getAllPersons(): array
    {
        return $this->makeRequestWithToken("GET", "Persons");
    }

    /**
     * @throws ConnectionFailedException
     * @throws ServerErrorException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws RequestInvalidException
     * @throws InvalidApiKeyException
     */
    public function getSinglePerson(int $userId): array
    {
        $this->checkToken();

        return $this->makeRequestWithToken("GET", "Persons/$userId");
    }

    /**
     * @throws ConnectionFailedException
     * @throws ServerErrorException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws InvalidApiKeyException
     * @throws RequestInvalidException
     */
    public function updatePerson(int $userId, array $userData): array
    {
        return $this->makeRequestWithToken("PATCH", "Persons/$userId", $userData);
    }

    /**
     * @throws ServerErrorException
     * @throws ConnectionFailedException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws InvalidApiKeyException
     * @throws RequestInvalidException
     */
    public function createPerson(array $userData): array
    {
        return $this->makeRequestWithToken("POST", "Persons", $userData);
    }

    /**
     * @throws ServerErrorException
     * @throws ConnectionFailedException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws InvalidApiKeyException
     * @throws RequestInvalidException
     */
    public function getPersonRoles(int $personId): array
    {
        return $this->makeRequestWithToken("GET", "Persons/$personId/Roles");
    }

    /**
     * @throws ServerErrorException
     * @throws ConnectionFailedException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws InvalidApiKeyException
     * @throws RequestInvalidException
     */
    public function addPersonRole(int $personId, string $roleName): array
    {
        return $this->makeRequestWithToken("POST", "Persons/$personId/AddRole/$roleName");
    }

    /**
     * @throws ServerErrorException
     * @throws ConnectionFailedException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws InvalidApiKeyException
     * @throws RequestInvalidException
     */
    public function removePersonRole(int $personId, string $roleName): array
    {
        return $this->makeRequestWithToken("POST", "Persons/$personId/RemoveRole/$roleName");
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

    /**
     * @throws ServerErrorException
     * @throws ConnectionFailedException
     * @throws InvalidTokenException
     * @throws RateLimitException
     * @throws InvalidResponseException
     * @throws InvalidApiKeyException
     * @throws RequestInvalidException
     */
    private function makeRequestWithToken(string $method, string $uri, ?array $body = null): array
    {
        $this->checkToken();

        return $this->makeRequest($method, $uri, $body, true);
    }

    /**
     * @throws RateLimitException
     * @throws InvalidApiKeyException
     * @throws InvalidTokenException
     * @throws RequestInvalidException
     * @throws ServerErrorException
     * @throws ConnectionFailedException
     * @throws InvalidResponseException
     */
    private function makeRequest(string $method, string $uri, ?array $body = null, bool $includeJwt = false): array
    {
        try {
            $response = $this->client->request($method, $uri, array_merge($this->getClientOptions($includeJwt), $this->getJsonBody($body)));

            if  ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return $this->convertIncomingResponseToArray($response);
            }

            return match ($response->getStatusCode()) {
                400 => throw new RequestInvalidException,
                401 => throw new InvalidTokenException,
                403 => throw new InvalidApiKeyException,
                429 => throw new RateLimitException,
                500 => throw new ServerErrorException,
                default => throw new InvalidResponseException()
            };
        } catch (GuzzleException $e) {
            throw new ConnectionFailedException($e->getMessage());
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

    /**
     * @throws InvalidResponseException
     */
    private function convertIncomingResponseToArray(ResponseInterface $response): array
    {
        try {
            $response->getBody()->rewind();
            $body = $response->getBody()->getContents();

            return (array) json_decode($body, true, 10, JSON_THROW_ON_ERROR);
        } catch (Exception) {
            throw new InvalidResponseException();
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
        return ['json' => $body];
    }
}
