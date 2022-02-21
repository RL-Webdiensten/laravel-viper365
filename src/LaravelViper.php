<?php

namespace RlWebdiensten\LaravelViper;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RlWebdiensten\LaravelViper\Contracts\ViperConfig;

class LaravelViper
{
    protected ViperConfig $config;

    function __construct(ViperConfig $config)
    {
        $this->config = $config;
    }

    function authenticateUser($username, $password): bool
    {
        $result = $this->makePostRequest("AuthenticateUser", [
            "Username" => $username,
            "Password" => $password
        ]);
        if (!isset($result['RefreshToken'], $result['Jwt'], $result['ExpiresIn'])) {
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
        if (!isset($result['Jwt'], $result['ExpiresIn'])) {
            return false;
        }

        $this->config->setJwtToken($result['Jwt']);
        $this->config->setJwtExpires($this->getDateFromExpiry($result['ExpiresIn']));
        $this->config->saveConfig();

        return true;
    }

    public function getAllPersons() : array
    {
        return $this->makeGetRequest("Persons", true);
    }

    public function getSinglePerson(int $userId) : array
    {
        return $this->makeGetRequest("Persons/$userId", true);
    }

    public function updatePerson(int $userId, array $userData) : array
    {
        return $this->makePatchRequest("Persons/$userId", $userData, true);
    }

    public function createPerson(array $userData) : array
    {
        return $this->makePostRequest("Persons", $userData, true);
    }

    public function makeGetRequest(string $uri, bool $includeJwt = false) : array
    {
        try {
            $client = $this->getClient($includeJwt);
            if (!$client) {
                return [];
            }

            $response = $client->get($uri);
            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $result = self::convertIncomingResponseToArray($response);
            if (!$result) {
                return [];
            }

            return $result;
        } catch (GuzzleException) {
            return [];
        }
    }

    public function makePostRequest(string $uri, array $jsonBody, bool $includeJwt = false) : array
    {
        try {
            $client = $this->getClient($includeJwt);
            if (!$client) {
                return [];
            }

            $response = $client->post($uri, ['json' => $jsonBody]);
            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $result = self::convertIncomingResponseToArray($response);
            if (!$result) {
                return [];
            }

            return $result;
        } catch (GuzzleException) {
            return [];
        }
    }

    public function makePatchRequest(string $uri, array $jsonBody, bool $includeJwt = false) : array
    {
        try {
            $client = $this->getClient($includeJwt);
            if (!$client) {
                return [];
            }

            $response = $client->patch($uri, ['json' => $jsonBody]);
            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $result = self::convertIncomingResponseToArray($response);
            if (!$result) {
                return [];
            }

            return $result;
        } catch (GuzzleException) {
            return [];
        }
    }

    private function getClient(bool $includeJwt = false): ?Client
    {
        if (!$this->config->getApiKey()) {
            return null;
        }

        $options = [
            'base_uri' => 'https://basic-api.viper365.net/v2/',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization-ApiKey' => $this->config->getApiKey(),
            ],
            'http_errors' => false,
            'debug' => false
        ];

        if ($includeJwt){
            if (!$this->config->getJwtToken()) {
                return null;
            }
            $options['headers']['Authorization-JWT'] = $this->config->getJwtToken();
        }

        return new Client($options);
    }

    private static function convertIncomingResponseToArray(ResponseInterface $response) : ?array
    {
        try {
            $response->getBody()->rewind();
            $body = $response->getBody()->getContents();
            return json_decode($body, true, 10, JSON_THROW_ON_ERROR);
        } catch (Exception) {
            return null;
        }
    }

    private function getDateFromExpiry($expiresIn): int
    {
        try{
            $dateTime = new DateTime();
            $dateTime->add(new DateInterval("PT".$expiresIn."S"));
            return $dateTime->getTimestamp();
        } catch (Exception){}
        return 0;
    }

    public function checkToken() {
        if (!$this->config->isTokenValid()) {
            $this->refreshToken();
        }
    }

}
