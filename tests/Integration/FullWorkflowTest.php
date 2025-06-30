<?php

namespace Tests\Integration;

use Amazeelabs\PolydockAppAmazeeioPrivateGpt\PolydockPrivateGptApp;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class FullWorkflowTest extends TestCase
{
    private PolydockPrivateGptApp $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new PolydockPrivateGptApp(
            'PrivateGPT',
            'Private GPT with amazee.ai integration',
            'FreedomTech Hosting',
            'https://freedomtech.host',
            'support@freedomtech.host',
            PolydockPrivateGptApp::getAppDefaultVariableDefinitions()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_has_correct_default_variable_definitions(): void
    {
        $definitions = PolydockPrivateGptApp::getAppDefaultVariableDefinitions();

        $this->assertCount(11, $definitions);

        $variableNames = array_map(fn ($def) => $def->getName(), $definitions);

        $this->assertContains('lagoon-deploy-git', $variableNames);
        $this->assertContains('lagoon-deploy-branch', $variableNames);
        $this->assertContains('lagoon-deploy-region-id', $variableNames);
        $this->assertContains('lagoon-deploy-private-key', $variableNames);
        $this->assertContains('lagoon-deploy-organization-id', $variableNames);
        $this->assertContains('lagoon-deploy-project-prefix', $variableNames);
        $this->assertContains('lagoon-project-name', $variableNames);
        $this->assertContains('lagoon-deploy-group-name', $variableNames);
        $this->assertContains('amazee-ai-api-key', $variableNames);
        $this->assertContains('amazee-ai-api-url', $variableNames);
        $this->assertContains('amazee-ai-admin-email', $variableNames);
    }

    public function test_requires_ai_infrastructure_by_default(): void
    {
        $this->assertTrue($this->app->getRequiresAiInfrastructure());
    }

    public function test_returns_correct_version(): void
    {
        $this->assertSame('0.0.1', PolydockPrivateGptApp::getAppVersion());
    }

    public function test_validates_required_lagoon_configuration_values(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        // Test with all required values present
        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['lagoon-deploy-git', 'https://github.com/amazeeio/privatgpt-template.git'],
                ['lagoon-deploy-region-id', '1'],
                ['lagoon-deploy-private-key', 'ssh-private-key'],
                ['lagoon-deploy-organization-id', '1'],
                ['lagoon-deploy-group-name', 'private-gpt-group'],
                ['lagoon-deploy-project-prefix', 'private-gpt'],
                ['lagoon-project-name', 'test-private-gpt-project'],
                ['polydock-app-instance-health-webhook-url', 'https://webhook.example.com'],
            ]);

        $appInstance->method('getAppType')
            ->willReturn('private-gpt');

        $result = $this->app->verifyLagoonValuesAreAvailable($appInstance);

        $this->assertTrue($result);
    }

    public function test_rejects_incomplete_lagoon_configuration(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        // Test with missing required value
        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['lagoon-deploy-git', ''], // Missing required value (empty string per interface contract)
                ['lagoon-deploy-region-id', '1'],
                ['lagoon-deploy-private-key', 'ssh-private-key'],
                ['lagoon-deploy-organization-id', '1'],
                ['lagoon-deploy-group-name', 'private-gpt-group'],
                ['lagoon-deploy-project-prefix', 'private-gpt'],
                ['lagoon-project-name', 'test-private-gpt-project'],
                ['polydock-app-instance-health-webhook-url', 'https://webhook.example.com'],
            ]);

        $appInstance->method('getAppType')
            ->willReturn('private-gpt');

        $result = $this->app->verifyLagoonValuesAreAvailable($appInstance);

        $this->assertFalse($result);
    }

    public function test_can_work_with_mocked_lagoon_client(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);
        $lagoonClient->method('pingLagoonAPI')->willReturn(true);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $result = $this->app->pingLagoonAPI();

        $this->assertTrue($result);
    }

    public function test_can_handle_lagoon_client_failures(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);
        $lagoonClient->method('pingLagoonAPI')->willThrowException(new \Exception('Connection timeout'));

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $this->expectException(\FreedomtechHosting\PolydockApp\PolydockAppInstanceStatusFlowException::class);
        $this->app->pingLagoonAPI();
    }

    public function test_can_work_with_mocked_amazee_ai_client(): void
    {
        $client = $this->createMock(\Amazeelabs\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $client->method('ping')->willReturn(true);

        $result = $client->ping();

        $this->assertTrue($result);
    }

    public function test_can_handle_amazee_ai_client_failures(): void
    {
        $client = $this->createMock(\Amazeelabs\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient::class);
        $client->method('ping')->willThrowException(new \Amazeelabs\PolydockAppAmazeeioPrivateGpt\Exceptions\AmazeeAiClientException('API is down'));

        $this->expectException(\Amazeelabs\PolydockAppAmazeeioPrivateGpt\Exceptions\AmazeeAiClientException::class);
        $client->ping();
    }

    public function test_can_add_or_update_lagoon_project_variables(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        // Mock successful variable update
        $lagoonClient->method('addOrUpdateScopedVariableForProject')
            ->with('test-project', 'AMAZEE_AI_API_KEY', 'test-key', 'BUILD')
            ->willReturn([
                'id' => 123,
                'name' => 'AMAZEE_AI_API_KEY',
                'value' => 'test-key',
                'scope' => 'BUILD',
            ]);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['lagoon-project-name', 'test-project'],
                ['lagoon-project-id', '123'],
            ]);

        // Should not throw exception when successful
        $this->app->addOrUpdateLagoonProjectVariable(
            $appInstance,
            'AMAZEE_AI_API_KEY',
            'test-key',
            'BUILD'
        );

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    public function test_handles_missing_lagoon_client_gracefully(): void
    {
        // No lagoon client set
        $this->expectException(\FreedomtechHosting\PolydockApp\PolydockAppInstanceStatusFlowException::class);
        $this->app->pingLagoonAPI();
    }

    public function test_handles_lagoon_api_errors_gracefully(): void
    {
        $lagoonClient = $this->createMock('FreedomtechHosting\FtLagoonPhp\Client');
        $lagoonClient->method('getDebug')->willReturn(false);
        $lagoonClient->method('pingLagoonAPI')
            ->willThrowException(new \Exception('Connection timeout'));

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $this->expectException(\FreedomtechHosting\PolydockApp\PolydockAppInstanceStatusFlowException::class);
        $this->app->pingLagoonAPI();
    }
}
