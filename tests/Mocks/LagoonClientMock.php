<?php

namespace Tests\Mocks;

use FreedomtechHosting\FtLagoonPhp\Client as LagoonClient;
use Mockery;

class LagoonClientMock
{
    public static function create(): object
    {
        /** @var \Mockery\MockInterface $mock */
        $mock = Mockery::mock(LagoonClient::class);

        $mock->allows('pingLagoonAPI')->andReturn(true);

        $mock->allows('getDebug')->andReturn(false);

        $mock->allows('createLagoonProjectInOrganization')->andReturn([
            'id' => 123,
            'name' => 'test-project',
            'git_url' => 'https://github.com/test/repo.git',
            'production_environment' => 'main',
            'auto_idle' => 1,
            'storage_calc' => 1,
            'problems_ui' => 1,
            'facts_ui' => 1,
            'production_routes' => '',
            'standby_production_environment' => '',
            'environments' => [],
        ]);

        $mock->allows('addOrUpdateScopedVariableForProject')->andReturn([
            'id' => 456,
            'name' => 'TEST_VAR',
            'value' => 'test_value',
            'scope' => 'BUILD',
        ]);

        $mock->allows('deployEnvironmentForProject')->andReturn([
            'id' => 789,
            'name' => 'main',
            'deploy_type' => 'branch',
            'environment_type' => 'production',
        ]);

        $mock->allows('getEnvironmentsByProjectName')->andReturn([
            [
                'id' => 789,
                'name' => 'main',
                'deploy_type' => 'branch',
                'environment_type' => 'production',
                'deploy_title' => 'deploy-title',
                'deployment_state' => 'complete',
                'route' => 'https://main-test-project.lagoon.example.com',
            ],
        ]);

        return $mock;
    }

    public static function createWithFailures(): object
    {
        /** @var \Mockery\MockInterface $mock */
        $mock = Mockery::mock(LagoonClient::class);

        $mock->allows('pingLagoonAPI')->andThrow(new \Exception('Connection timeout'));

        $mock->allows('createLagoonProjectInOrganization')->andReturn(['error' => 'Failed to create project']);

        return $mock;
    }
}
