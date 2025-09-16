<?php

namespace Amazeeio\PolydockAppAmazeeioPrivateGpt\Client;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Exceptions\AmazeeAiClientException;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Exceptions\AmazeeAiValidationException;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\AdministratorResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\HealthResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\LlmKeysResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\RegionResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\TeamResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\VdbKeysResponse;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class AmazeeAiClient
{
    private ClientInterface $httpClient;

    private string $apiUrl;

    private string $apiKey;

    private \CuyZ\Valinor\Mapper\TreeMapper $mapper;

    public function __construct(string $apiKey, string $apiUrl = 'https://api.amazee.ai', ?ClientInterface $httpClient = null)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/');

        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $this->mapper = (new MapperBuilder)
            ->allowSuperfluousKeys()
            ->allowPermissiveTypes()
            ->allowUndefinedValues() // To handle missing nullable fields gracefully
            ->mapper();
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $className
     * @param  array<string, mixed>  $data
     * @return T
     *
     * @throws AmazeeAiValidationException
     */
    private function mapResponse(string $className, array $data): object
    {
        try {
            return $this->mapper->map($className, $data);
        } catch (MappingError $error) {
            throw new AmazeeAiValidationException(
                'Failed to validate API response',
                $error
            );
        }
    }

    public function createTeam(string $name, string $adminEmail): TeamResponse
    {
        try {
            $response = $this->httpClient->request('POST', '/teams', [
                'json' => [
                    'name' => $name,
                    'admin_email' => $adminEmail,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $this->mapResponse(TeamResponse::class, $data);
        } catch (RequestException $e) {
            throw new AmazeeAiClientException(
                'Failed to create team: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    // Adding this simply for downstream convenience.
    public function deleteTeam(string $teamId): string
    {
        try {
            $response = $this->httpClient->request('DELETE', "/teams/{$teamId}");

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['message'];
        } catch (RequestException $e) {
            throw new AmazeeAiClientException(
                'Failed to delete team: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function addTeamAdministrator(string $teamId, string $email): AdministratorResponse
    {
        try {
            $response = $this->httpClient->request('POST', "/teams/{$teamId}/administrators", [
                'json' => [
                    'email' => $email,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $this->mapResponse(AdministratorResponse::class, $data);
        } catch (RequestException $e) {
            throw new AmazeeAiClientException(
                'Failed to add team administrator: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @return RegionResponse[]
     */
    public function getRegions(): array
    {
        try {
            $response = $this->httpClient->request('GET', '/regions');

            $data = json_decode($response->getBody()->getContents(), true);

            // Assuming $data is an array of regions
            return array_map(
                fn ($region) => $this->mapResponse(RegionResponse::class, $region),
                $data
            );
        } catch (RequestException $e) {
            throw new AmazeeAiClientException(
                'Failed to get regions: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function createLlmKey(int $teamId, int $regionId): LlmKeysResponse
    {
        try {
            $response = $this->httpClient->request('POST', '/private-ai-keys/token', [
                'json' => [
                    'team_id' => $teamId,
                    'region_id' => $regionId,
                    'name' => sprintf('llm-%d-%d', $regionId, $teamId),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $this->mapResponse(LlmKeysResponse::class, $data);
        } catch (RequestException $e) {
            throw new AmazeeAiClientException(
                'Failed to generate LLM keys: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function generateLlmKeys(string $teamId): LlmKeysResponse
    {
        try {
            $response = $this->httpClient->request('POST', "/v1/teams/{$teamId}/keys/llm", [
                'json' => [],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $this->mapResponse(LlmKeysResponse::class, $data);
        } catch (RequestException $e) {
            throw new AmazeeAiClientException(
                'Failed to generate LLM keys: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function generateVdbKeys(string $teamId): VdbKeysResponse
    {
        try {
            $response = $this->httpClient->request('POST', "/v1/teams/{$teamId}/keys/vdb", [
                'json' => [],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $this->mapResponse(VdbKeysResponse::class, $data);
        } catch (RequestException $e) {
            throw new AmazeeAiClientException(
                'Failed to generate VDB keys: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function getTeam(string $teamId): TeamResponse
    {
        try {
            $response = $this->httpClient->request('GET', "/v1/teams/{$teamId}");

            $data = json_decode($response->getBody()->getContents(), true);

            return $this->mapResponse(TeamResponse::class, $data);
        } catch (RequestException $e) {
            throw new AmazeeAiClientException(
                'Failed to get team: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function health(): HealthResponse
    {
        try {
            $response = $this->httpClient->request('GET', '/health');

            $data = json_decode($response->getBody()->getContents(), true);

            return $this->mapResponse(HealthResponse::class, $data);
        } catch (RequestException $e) {
            throw new AmazeeAiClientException(
                'Failed to check health: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function ping(): bool
    {
        try {
            $health = $this->health();

            return $health->status === 'healthy';
        } catch (AmazeeAiClientException $e) {
            return false;
        }
    }
}
