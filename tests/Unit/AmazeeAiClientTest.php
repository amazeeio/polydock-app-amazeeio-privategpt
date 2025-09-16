<?php

namespace Tests\Unit;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Exceptions\AmazeeAiClientException;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Exceptions\AmazeeAiValidationException;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\AdministratorResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\HealthResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\LlmKeysResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\TeamResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\VdbKeysResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AmazeeAiClientTest extends TestCase
{
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = 'test-api-key';
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test client instantiation with API key and default URL
     */
    public function test_instantiation_with_default_url(): void
    {
        $client = new AmazeeAiClient($this->apiKey);

        $this->assertInstanceOf(AmazeeAiClient::class, $client);
    }

    /**
     * Test client instantiation with custom API URL
     */
    public function test_instantiation_with_custom_url(): void
    {
        $customUrl = 'https://custom.api.example.com';
        $client = new AmazeeAiClient($this->apiKey, $customUrl);

        $this->assertInstanceOf(AmazeeAiClient::class, $client);
    }

    /**
     * Test successful team creation
     */
    public function test_create_team_success(): void
    {
        $responseBody = file_get_contents(__DIR__.'/../Mocks/MockResponses/amazeeai/team_creation_success.json');
        if ($responseBody === false) {
            $this->fail('Failed to read mock response file');
        }

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $responseBody),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $result = $client->createTeam('test-team', 'admin@example.com');

        $this->assertInstanceOf(TeamResponse::class, $result);
        $this->assertSame(123, $result->id);
        $this->assertSame('test-project-private-gpt', $result->name);
        $this->assertSame('admin@example.com', $result->admin_email);
    }

    /**
     * Test team creation failure throws exception
     */
    public function test_create_team_failure(): void
    {
        $mock = new MockHandler([
            new RequestException('Server Error', new Request('POST', '/v1/teams')),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(AmazeeAiClientException::class);
        $this->expectExceptionMessage('Failed to create team');
        $client->createTeam('test-team', 'admin@example.com');
    }

    /**
     * Test team creation sends correct request data
     */
    public function test_create_team_request_data(): void
    {
        $validResponse = json_encode([
            'name' => 'my-team',
            'admin_email' => 'admin@test.com',
            'phone' => null,
            'billing_address' => null,
            'id' => 123,
            'is_active' => true,
            'is_always_free' => false,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
            'last_payment' => null,
        ]);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $validResponse ?: ''),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $client->createTeam('my-team', 'admin@test.com');

        $lastRequest = $mock->getLastRequest();
        if ($lastRequest) {
            $requestBody = json_decode($lastRequest->getBody()->getContents(), true);

            $this->assertSame([
                'name' => 'my-team',
                'admin_email' => 'admin@test.com',
            ], $requestBody);
        }
    }

    /**
     * Test successful team administrator addition
     */
    public function test_add_administrator_success(): void
    {
        $responseData = [
            'email' => 'admin@example.com',
            'id' => 456,
            'is_active' => true,
            'is_admin' => true,
            'team_id' => 123,
            'team_name' => 'test-team',
            'role' => 'administrator',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData) ?: ''),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $result = $client->addTeamAdministrator('team-123', 'admin@example.com');

        $this->assertInstanceOf(AdministratorResponse::class, $result);
        $this->assertSame(456, $result->id);
        $this->assertSame(123, $result->team_id);
        $this->assertSame('admin@example.com', $result->email);
        $this->assertSame('administrator', $result->role);
    }

    /**
     * Test team administrator addition failure throws exception
     */
    public function test_add_administrator_failure(): void
    {
        $mock = new MockHandler([
            new RequestException('Forbidden', new Request('POST', '/v1/teams/team-123/administrators')),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(AmazeeAiClientException::class);
        $this->expectExceptionMessage('Failed to add team administrator');
        $client->addTeamAdministrator('team-123', 'admin@example.com');
    }

    /**
     * Test successful LLM keys generation
     */
    public function test_generate_llm_keys_success(): void
    {
        $responseBody = file_get_contents(__DIR__.'/../Mocks/MockResponses/amazeeai/llm_keys_generation_success.json');
        if ($responseBody === false) {
            $this->fail('Failed to read mock response file');
        }
        $responseData = json_decode($responseBody, true);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $responseBody),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $result = $client->generateLlmKeys('team-123');

        $this->assertInstanceOf(LlmKeysResponse::class, $result);
        $this->assertSame('llm-key-abc123def456', $result->litellm_token);
        $this->assertNotNull($result->id);
    }

    /**
     * Test LLM keys generation failure throws exception
     */
    public function test_generate_llm_keys_failure(): void
    {
        $mock = new MockHandler([
            new RequestException('Service Unavailable', new Request('POST', '/v1/teams/team-123/keys/llm')),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(AmazeeAiClientException::class);
        $this->expectExceptionMessage('Failed to generate LLM keys');
        $client->generateLlmKeys('team-123');
    }

    /**
     * Test successful VDB keys generation
     */
    public function test_generate_vdb_keys_success(): void
    {
        $responseBody = file_get_contents(__DIR__.'/../Mocks/MockResponses/amazeeai/vdb_keys_generation_success.json');
        if ($responseBody === false) {
            $this->fail('Failed to read mock response file');
        }
        $responseData = json_decode($responseBody, true);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $responseBody),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $result = $client->generateVdbKeys('team-123');

        $this->assertInstanceOf(VdbKeysResponse::class, $result);
        $this->assertSame('vdb-key-xyz789uvw012', $result->litellm_token);
        $this->assertNotNull($result->name);
    }

    /**
     * Test VDB keys generation failure throws exception
     */
    public function test_generate_vdb_keys_failure(): void
    {
        $mock = new MockHandler([
            new RequestException('Service Unavailable', new Request('POST', '/v1/teams/team-123/keys/vdb')),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(AmazeeAiClientException::class);
        $this->expectExceptionMessage('Failed to generate VDB keys');
        $client->generateVdbKeys('team-123');
    }

    /**
     * Test successful team details retrieval
     */
    public function test_get_team_success(): void
    {
        $responseData = [
            'name' => 'test-team',
            'admin_email' => 'admin@example.com',
            'phone' => null,
            'billing_address' => null,
            'id' => 123,
            'is_active' => true,
            'is_always_free' => false,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
            'last_payment' => null,
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData) ?: ''),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $result = $client->getTeam('team-123');

        $this->assertInstanceOf(TeamResponse::class, $result);
        $this->assertSame(123, $result->id);
        $this->assertSame('test-team', $result->name);
        $this->assertSame('admin@example.com', $result->admin_email);
    }

    /**
     * Test team details retrieval failure throws exception
     */
    public function test_get_team_failure(): void
    {
        $mock = new MockHandler([
            new RequestException('Not Found', new Request('GET', '/v1/teams/team-123')),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(AmazeeAiClientException::class);
        $this->expectExceptionMessage('Failed to get team');
        $client->getTeam('team-123');
    }

    /**
     * Test successful health status check
     */
    public function test_health_check_success(): void
    {
        $responseBody = file_get_contents(__DIR__.'/../Mocks/MockResponses/amazeeai/health_check_success.json');
        if ($responseBody === false) {
            $this->fail('Failed to read mock response file');
        }
        $responseData = json_decode($responseBody, true);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $responseBody),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $result = $client->health();

        $this->assertInstanceOf(HealthResponse::class, $result);
        $this->assertSame('healthy', $result->status);
    }

    /**
     * Test health check failure throws exception
     */
    public function test_health_check_failure(): void
    {
        $mock = new MockHandler([
            new RequestException('Service Unavailable', new Request('GET', '/health')),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(AmazeeAiClientException::class);
        $this->expectExceptionMessage('Failed to check health');
        $client->health();
    }

    /**
     * Test ping returns true when service is healthy
     */
    public function test_ping_healthy(): void
    {
        $healthResponse = [
            'status' => 'healthy',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($healthResponse) ?: ''),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $result = $client->ping();

        $this->assertTrue($result);
    }

    /**
     * Test ping returns false when service is unhealthy
     */
    public function test_ping_unhealthy(): void
    {
        $healthResponse = [
            'status' => 'unhealthy',
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($healthResponse) ?: ''),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $result = $client->ping();

        $this->assertFalse($result);
    }

    /**
     * Test ping returns false when health check throws exception
     */
    public function test_ping_exception(): void
    {
        $mock = new MockHandler([
            new RequestException('Connection failed', new Request('GET', '/health')),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $result = $client->ping();

        $this->assertFalse($result);
    }

    /**
     * Test validation error when response has missing required fields
     */
    public function test_create_team_validation_error(): void
    {
        $invalidResponse = json_encode(['name' => 'test-team']); // missing required fields
        if ($invalidResponse === false) {
            $this->fail('Failed to encode JSON');
        }

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $invalidResponse),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(AmazeeAiValidationException::class);
        $this->expectExceptionMessage('Failed to validate API response');
        $client->createTeam('test-team', 'admin@example.com');
    }

    /**
     * Test validation error when health response has wrong format
     */
    public function test_health_validation_error(): void
    {
        $invalidResponse = json_encode(['invalid' => 'data']);
        if ($invalidResponse === false) {
            $this->fail('Failed to encode JSON');
        }

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $invalidResponse),
        ]);

        $client = $this->createClientWithMockHandler($mock);

        $this->expectException(AmazeeAiValidationException::class);
        $this->expectExceptionMessage('Failed to validate API response');
        $client->health();
    }

    private function createClientWithMockHandler(MockHandler $mockHandler): AmazeeAiClient
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new AmazeeAiClient('test-api-key', 'https://api.amazee.ai');

        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        return $client;
    }
}
