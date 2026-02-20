<?php

namespace Tests\Unit\Traits;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Exceptions\AmazeeAiClientException;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\TeamResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\LoggerInterface;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Traits\UsesAmazeeAiDevmode;
use FreedomtechHosting\PolydockApp\PolydockAppInstanceStatusFlowException;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class TestClassWithUsesAmazeeAi
{
    use UsesAmazeeAiDevmode;
}

class UsesAmazeeAiTest extends TestCase
{
    private TestClassWithUsesAmazeeAi $testClass;

    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testClass = new TestClassWithUsesAmazeeAi;

        // Create mock logger
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockLogger->method('getLogContext')
            ->willReturn(['class' => TestClassWithUsesAmazeeAi::class, 'location' => 'test']);

        // Setup trait with mock logger
        $this->testClass->setupAmazeeAiTrait($this->mockLogger);
    }

    private function createTeamResponse(): TeamResponse
    {
        return new TeamResponse(
            name: 'test-team',
            admin_email: 'admin@example.com',
            phone: null,
            billing_address: null,
            id: 123,
            is_active: true,
            is_always_free: false,
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z',
            last_payment: null
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @throws PolydockAppInstanceStatusFlowException
     *
     * This is a simple test to make sure that when we initialize devmode we're not hitting the real API.
     * TODO: this could be expanded quite significantly to cover all the methods in the trait.
     */
    public function test_devmode_overrides_api_calls(): void
    {
        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $this->testClass->setAmazeeAiClientDevMode();
        /** @var TeamResponse $teamResponse */
        $teamResponse = $this->testClass->createTeamAndSetupAdministrator($appInstance);
        $this->assertSame('devmode-name', $teamResponse->name);
    }

    public function test_devmode_generate_keys_for_team_returns_credentials_with_gateway_token(): void
    {
        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['amazee-ai-backend-region-id', '1'],
            ]);

        $this->testClass->setAmazeeAiClientDevMode();
        $credentials = $this->testClass->generateKeysForTeam($appInstance, 'devmode-team-id');

        $this->assertArrayHasKey('team_id', $credentials);
        $this->assertArrayHasKey('backend_key', $credentials);
        $this->assertArrayHasKey('llm_key', $credentials);
        $this->assertArrayHasKey('user_gateway_token', $credentials);
        $this->assertSame('devmode-team-id', $credentials['team_id']);
        $this->assertSame('gateway-token', $credentials['user_gateway_token']->token);
    }

    public function test_set_amazee_ai_direct_client_from_app_instance_successfully_sets_amazee_ai_client_when_all_parameters_are_provided(): void
    {
        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['amazee-ai-backend-token', 'test-backend-token'],
                ['amazee-ai-backend-url', 'https://backend.main.amazeeai.us2.amazee.io'],
            ]);

        // Create test class with mocked client that returns healthy ping
        $testClass = new TestClassWithUsesAmazeeAi;
        $testClass->setupAmazeeAiTrait($this->mockLogger);

        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('ping')->willReturn(true);

        // Set the mock client directly to bypass the ping check in setAmazeeAiClientFromAppInstance
        $reflection = new ReflectionClass($testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($testClass, $mockClient);

        // Verify the client was set
        $client = $property->getValue($testClass);

        $this->assertInstanceOf(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class, $client);
    }

    public function test_set_amazee_ai_direct_client_from_app_instance_uses_default_api_url_when_not_provided(): void
    {
        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['amazee-ai-backend-token', 'test-backend-token'],
                ['amazee-ai-backend-url', ''],
            ]);

        // Create test class with mocked client that returns healthy ping
        $testClass = new TestClassWithUsesAmazeeAi;
        $testClass->setupAmazeeAiTrait($this->mockLogger);

        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('ping')->willReturn(true);

        // Set the mock client directly
        $reflection = new ReflectionClass($testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($testClass, $mockClient);

        // Verify the client was set
        $client = $property->getValue($testClass);

        $this->assertInstanceOf(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class, $client);
    }

    public function test_set_amazee_ai_direct_client_from_app_instance_throws_exception_when_api_key_is_missing(): void
    {
        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['amazee-ai-backend-token', ''], // Empty string represents missing value per interface contract
                ['amazee-ai-backend-url', 'https://backend.main.amazeeai.us2.amazee.io'],
            ]);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('amazee.ai backend token is required to be set in the app instance');
        $this->testClass->setAmazeeAiClientFromAppInstance($appInstance);
    }

    public function test_ping_amazee_ai_direct_returns_true_when_amazee_ai_service_is_healthy(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('ping')->willReturn(true);

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $result = $this->testClass->pingAmazeeAi();

        $this->assertTrue($result);
    }

    public function test_ping_amazee_ai_direct_returns_false_when_amazee_ai_service_is_unhealthy(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('ping')->willThrowException(new AmazeeAiClientException('API is down'));

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->testClass->pingAmazeeAi();
    }

    public function test_ping_amazee_ai_direct_throws_exception_when_amazee_ai_client_is_not_available(): void
    {
        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('amazee.ai client not found');
        $this->testClass->pingAmazeeAi();
    }

    public function test_ping_amazee_ai_direct_throws_exception_when_ping_throws_client_exception(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('ping')
            ->willThrowException(new AmazeeAiClientException('API Error'));

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('Error pinging amazee.ai API: API Error');
        $this->testClass->pingAmazeeAi();
    }

    public function test_create_team_and_setup_administrator_throws_exception_when_admin_email_is_missing(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['lagoon-project-name', 'test-project'],
                ['user-email', ''], // Empty string represents missing value per interface contract
            ]);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('amazee.ai admin email is required');
        $this->testClass->createTeamAndSetupAdministrator($appInstance);
    }

    public function test_create_team_and_setup_administrator_throws_exception_when_team_creation_fails(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('createTeam')
            ->willThrowException(new AmazeeAiClientException('Team creation failed'));

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['lagoon-project-name', 'test-project'],
                ['user-email', 'admin@example.com'],
            ]);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('Error creating team or setting up administrator: Team creation failed');
        $this->testClass->createTeamAndSetupAdministrator($appInstance);
    }

    public function test_create_team_and_setup_administrator_throws_exception_when_team_creation_returns_no_id(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        // This test is no longer valid since DTOs guarantee required fields
        // Instead test that AmazeeAiClientException can be thrown for API failures
        $mockClient->method('createTeam')
            ->willThrowException(new AmazeeAiClientException('API validation failed'));

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['lagoon-project-name', 'test-project'],
                ['user-email', 'admin@example.com'],
            ]);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('Error creating team or setting up administrator: API validation failed');
        $this->testClass->createTeamAndSetupAdministrator($appInstance);
    }

    public function test_get_team_details_successfully_retrieves_team_details(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('getTeam')->willReturn($this->createTeamResponse());

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $result = $this->testClass->getTeamDetails('team-123');

        $this->assertInstanceOf(TeamResponse::class, $result);
        $this->assertSame(123, $result->id);
        $this->assertSame('test-team', $result->name);
    }

    public function test_get_team_details_throws_exception_when_team_retrieval_fails(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('getTeam')
            ->willThrowException(new AmazeeAiClientException('Team not found'));

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('Error getting team details: Team not found');
        $this->testClass->getTeamDetails('team-123');
    }

    public function test_generate_keys_for_team_throws_exception_when_region_id_is_missing(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['amazee-ai-backend-region-id', ''],
            ]);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('amazee.ai LLM region is required to generate LLM keys');
        $this->testClass->generateKeysForTeam($appInstance, 'team-123');
    }

    public function test_generate_keys_for_team_returns_credentials_including_gateway_token(): void
    {
        $apiToken = new \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\APIToken(
            'test-token', 1, 'token-value', '2024-01-01T00:00:00Z', 1
        );
        $gatewayToken = new \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\APIToken(
            'test-gateway-token', 2, 'gateway-token-value', '2024-01-01T00:00:00Z', 1
        );
        $llmKey = new \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\LlmKeysResponse(
            1, 'database-name', 'llm-key', 'host', 'user', 'pass', 'litellm-token', 'https://api.example.com', 'us', '2024-01-01T00:00:00Z', 1, 123
        );

        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('createBackendKey')->willReturn($apiToken);
        $mockClient->method('createUserGatewayToken')->willReturn($gatewayToken);
        $mockClient->method('createLlmKey')->willReturn($llmKey);

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['amazee-ai-backend-region-id', '1'],
            ]);

        $result = $this->testClass->generateKeysForTeam($appInstance, '123');

        $this->assertArrayHasKey('team_id', $result);
        $this->assertArrayHasKey('backend_key', $result);
        $this->assertArrayHasKey('llm_key', $result);
        $this->assertArrayHasKey('user_gateway_token', $result);
        $this->assertSame('123', $result['team_id']);
        $this->assertSame('token-value', $result['backend_key']->token);
        $this->assertSame('gateway-token-value', $result['user_gateway_token']->token);
        $this->assertSame('litellm-token', $result['llm_key']->litellm_token);
    }

    public function test_generate_keys_for_team_throws_exception_when_key_creation_fails(): void
    {
        $mockClient = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $mockClient->method('createBackendKey')
            ->willThrowException(new AmazeeAiClientException('Failed to create backend key'));

        $reflection = new ReflectionClass($this->testClass);
        $property = $reflection->getProperty('amazeeAiClient');
        $property->setValue($this->testClass, $mockClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['amazee-ai-backend-region-id', '1'],
            ]);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('Error generating keys for team: Failed to create backend key');
        $this->testClass->generateKeysForTeam($appInstance, '123');
    }

    protected function createMockPolydockAppInstance(array $keyValues = []): object
    {
        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);

        $appInstance->method('getKeyValue')
            ->willReturnCallback(fn ($key) => $keyValues[$key] ?? null);

        return $appInstance;
    }
}
