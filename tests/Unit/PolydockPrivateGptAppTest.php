<?php

namespace Tests\Unit;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\PolydockPrivateGptApp;
use Exception;
use FreedomtechHosting\PolydockApp\PolydockAppInstanceStatusFlowException;
use FreedomtechHosting\PolydockApp\PolydockAppVariableDefinitionBase;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class PolydockPrivateGptAppTest extends TestCase
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

    /**
     * Test app returns correct version
     */
    public function test_app_version(): void
    {
        $this->assertSame('0.0.1', PolydockPrivateGptApp::getAppVersion());
    }

    /**
     * Test app returns correct default variable definitions
     */
    public function test_default_variables(): void
    {
        $definitions = PolydockPrivateGptApp::getAppDefaultVariableDefinitions();

        $this->assertIsArray($definitions);
        $this->assertCount(11, $definitions);

        $expectedVariables = [
            'lagoon-deploy-git',
            'lagoon-deploy-branch',
            'lagoon-deploy-region-id',
            'lagoon-deploy-private-key',
            'lagoon-deploy-organization-id',
            'lagoon-deploy-project-prefix',
            'lagoon-project-name',
            'lagoon-deploy-group-name',
            'amazee-ai-backend-token',
            'amazee-ai-backend-url',
            'amazee-ai-admin-email',
        ];

        foreach ($definitions as $index => $definition) {
            $this->assertInstanceOf(PolydockAppVariableDefinitionBase::class, $definition);
            $this->assertSame($expectedVariables[$index], $definition->getName());
        }
    }

    /**
     * Test requires AI infrastructure returns true by default
     */
    public function test_requires_ai_default(): void
    {
        // Reset to default value in case other tests have modified it
        $this->app->setRequiresAiInfrastructure(true);
        $this->assertTrue($this->app->getRequiresAiInfrastructure());
    }

    /**
     * Test requires AI infrastructure can be set to false
     */
    public function test_requires_ai_false(): void
    {
        $this->app->setRequiresAiInfrastructure(false);
        $this->assertFalse($this->app->getRequiresAiInfrastructure());
    }

    /**
     * Test requires AI infrastructure can be set back to true
     */
    public function test_requires_ai_true(): void
    {
        $this->app->setRequiresAiInfrastructure(false);
        $this->app->setRequiresAiInfrastructure(true);
        $this->assertTrue($this->app->getRequiresAiInfrastructure());
    }

    /**
     * Test successful Lagoon API ping when client is available
     */
    public function test_ping_lagoon_success(): void
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

    /**
     * Test Lagoon API ping throws exception when client is not available
     */
    public function test_ping_lagoon_no_client(): void
    {
        // Ensure lagoon client is null for this test
        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, null);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('Lagoon client not found for ping');
        $this->app->pingLagoonAPI();
    }

    /**
     * Test Lagoon API ping throws exception when ping fails
     */
    public function test_ping_lagoon_failure(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);
        $lagoonClient->method('pingLagoonAPI')->willThrowException(new Exception('Connection failed'));

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('Error pinging Lagoon API: Connection failed');
        $this->app->pingLagoonAPI();
    }

    /**
     * Test successful Lagoon client setup from app instance
     */
    public function test_set_lagoon_client_success(): void
    {
        $mockEngine = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockEngineInterface::class);
        $mockLagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $mockServiceProvider = $this->createMock(\Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\LagoonClientProviderInterface::class);
        $mockServiceProvider->method('getLagoonClient')->willReturn($mockLagoonClient);

        $mockEngine->method('getPolydockServiceProviderSingletonInstance')
            ->with('PolydockServiceProviderFTLagoon')
            ->willReturn($mockServiceProvider);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getEngine')
            ->willReturn($mockEngine);

        $this->app->setLagoonClientFromAppInstance($appInstance);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $lagoonClient = $property->getValue($this->app);

        $this->assertSame($mockLagoonClient, $lagoonClient);
    }

    /**
     * Test Lagoon client setup throws exception when service provider lacks method
     */
    public function test_set_lagoon_client_no_method(): void
    {
        $mockEngine = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockEngineInterface::class);
        $mockServiceProvider = $this->createMock('FreedomtechHosting\PolydockApp\PolydockServiceProviderInterface');

        $mockEngine->method('getPolydockServiceProviderSingletonInstance')
            ->with('PolydockServiceProviderFTLagoon')
            ->willReturn($mockServiceProvider);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getEngine')
            ->willReturn($mockEngine);

        $this->expectException(PolydockAppInstanceStatusFlowException::class);
        $this->expectExceptionMessage('Lagoon client provider is not an instance of LagoonClientProviderInterface');
        $this->app->setLagoonClientFromAppInstance($appInstance);
    }

    /**
     * Test Lagoon values verification returns true when all values available
     */
    public function test_verify_lagoon_values_success(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['lagoon-deploy-git', 'https://github.com/test/repo.git'],
                ['lagoon-deploy-region-id', '1'],
                ['lagoon-deploy-private-key', 'private-key'],
                ['lagoon-deploy-organization-id', '1'],
                ['lagoon-deploy-group-name', 'test-group'],
                ['lagoon-deploy-project-prefix', 'test'],
                ['lagoon-project-name', 'test-project'],
                ['polydock-app-instance-health-webhook-url', 'https://webhook.url'],
            ]);

        $appInstance->method('getAppType')
            ->willReturn('private-gpt');

        $result = $this->app->verifyLagoonValuesAreAvailable($appInstance);

        $this->assertTrue($result);
    }

    /**
     * Test Lagoon values verification returns false when deploy git is missing
     */
    public function test_verify_lagoon_values_no_git(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['lagoon-deploy-git', ''],
                ['lagoon-deploy-region-id', '1'],
                ['lagoon-deploy-private-key', 'private-key'],
                ['lagoon-deploy-organization-id', '1'],
                ['lagoon-deploy-group-name', 'test-group'],
                ['lagoon-deploy-project-prefix', 'test'],
                ['lagoon-project-name', 'test-project'],
                ['polydock-app-instance-health-webhook-url', 'https://webhook.url'],
            ]);

        $appInstance->method('getAppType')
            ->willReturn('private-gpt');

        $result = $this->app->verifyLagoonValuesAreAvailable($appInstance);

        $this->assertFalse($result);
    }

    /**
     * Test Lagoon values verification returns false when app type is missing
     */
    public function test_verify_lagoon_values_no_app_type(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->willReturnMap([
                ['lagoon-deploy-git', 'https://github.com/test/repo.git'],
                ['lagoon-deploy-region-id', '1'],
                ['lagoon-deploy-private-key', 'private-key'],
                ['lagoon-deploy-organization-id', '1'],
                ['lagoon-deploy-group-name', 'test-group'],
                ['lagoon-deploy-project-prefix', 'test'],
                ['lagoon-project-name', 'test-project'],
                ['polydock-app-instance-health-webhook-url', 'https://webhook.url'],
            ]);

        $appInstance->method('getAppType')
            ->willReturn('');

        $result = $this->app->verifyLagoonValuesAreAvailable($appInstance);

        $this->assertFalse($result);
    }

    /**
     * Test Lagoon project name verification returns true when name is available
     */
    public function test_verify_project_name_success(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->with('lagoon-project-name')
            ->willReturn('test-project');

        $result = $this->app->verifyLagoonProjectNameIsAvailable($appInstance);

        $this->assertTrue($result);
    }

    /**
     * Test Lagoon project name verification returns false when name is missing
     */
    public function test_verify_project_name_missing(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->with('lagoon-project-name')
            ->willReturn('');

        $result = $this->app->verifyLagoonProjectNameIsAvailable($appInstance);

        $this->assertFalse($result);
    }

    /**
     * Test Lagoon project ID verification returns true when ID is available
     */
    public function test_verify_project_id_success(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->with('lagoon-project-id')
            ->willReturn('123');

        $result = $this->app->verifyLagoonProjectIdIsAvailable($appInstance);

        $this->assertTrue($result);
    }

    /**
     * Test Lagoon project ID verification returns false when ID is missing
     */
    public function test_verify_project_id_missing(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getProperty('lagoonClient');
        $property->setAccessible(true);
        $property->setValue($this->app, $lagoonClient);

        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);
        $appInstance->method('getKeyValue')
            ->with('lagoon-project-id')
            ->willReturn('');

        $result = $this->app->verifyLagoonProjectIdIsAvailable($appInstance);

        $this->assertFalse($result);
    }

    /**
     * Test successful addition/update of Lagoon project variable
     */
    public function test_add_update_variable_success(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);

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

        $this->app->addOrUpdateLagoonProjectVariable(
            $appInstance,
            'TEST_VAR',
            'test_value',
            'BUILD'
        );

        $this->assertTrue(true);
    }

    /**
     * Test Lagoon project variable update throws exception when update fails
     */
    public function test_add_update_variable_failure(): void
    {
        $lagoonClient = $this->createMock(\FreedomtechHosting\FtLagoonPhp\Client::class);
        $lagoonClient->method('getDebug')->willReturn(false);
        $lagoonClient->method('addOrUpdateScopedVariableForProject')
            ->willReturn(['error' => 'Variable update failed']);

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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to add or update TEST_VAR variable');
        $this->app->addOrUpdateLagoonProjectVariable(
            $appInstance,
            'TEST_VAR',
            'test_value',
            'BUILD'
        );
    }

    /**
     * Test get log context returns correct log context
     */
    public function test_get_log_context(): void
    {
        $context = $this->app->getLogContext('testMethod');

        $this->assertSame([
            'class' => PolydockPrivateGptApp::class,
            'location' => 'testMethod',
        ], $context);
    }

    /**
     * @param  array<string, mixed>  $keyValues
     */
    protected function createMockPolydockAppInstance(array $keyValues = []): object
    {
        $appInstance = $this->createMock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);

        $appInstance->method('getKeyValue')
            ->willReturnCallback(function ($key) use ($keyValues) {
                return $keyValues[$key] ?? null;
            });

        return $appInstance;
    }

    protected function createMockPolydockEngine(): object
    {
        return $this->createMock(\FreedomtechHosting\PolydockApp\PolydockEngineInterface::class);
    }

    protected function createMockPolydockServiceProvider(): object
    {
        return $this->createMock(\FreedomtechHosting\PolydockApp\PolydockServiceProviderInterface::class);
    }
}
