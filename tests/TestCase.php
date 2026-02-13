<?php

namespace Tests;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\Override]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $keyValues
     */
    protected function createMockPolydockAppInstance(array $keyValues = []): object
    {
        /** @var \Mockery\MockInterface $mock */
        $mock = Mockery::mock(\FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface::class);

        foreach ($keyValues as $key => $value) {
            $mock->shouldReceive('getKeyValue')
                ->with($key)
                ->andReturn($value);
        }

        // Default return empty string for any unspecified keys (per interface contract)
        $mock->shouldReceive('getKeyValue')
            ->byDefault()
            ->andReturn('');

        $mock->shouldReceive('setStatus')
            ->andReturnSelf();

        $mock->shouldReceive('save')
            ->andReturnSelf();

        return $mock;
    }

    protected function createMockPolydockEngine(): object
    {
        /** @var \Mockery\MockInterface $mock */
        $mock = Mockery::mock(\FreedomtechHosting\PolydockApp\PolydockEngineInterface::class);

        return $mock;
    }

    protected function createMockPolydockServiceProvider(): object
    {
        /** @var \Mockery\MockInterface $mock */
        $mock = Mockery::mock(\FreedomtechHosting\PolydockApp\PolydockServiceProviderInterface::class);

        // Add default expectations for interface methods
        $mock->shouldReceive('getName')->byDefault()->andReturn('test-provider');
        $mock->shouldReceive('getDescription')->byDefault()->andReturn('test-provider');
        $mock->shouldReceive('setLogger')->byDefault()->andReturnSelf();
        $mock->shouldReceive('getLogger')->byDefault()->andReturn(Mockery::mock(\FreedomtechHosting\PolydockApp\PolydockAppLoggerInterface::class));
        $mock->shouldReceive('info')->byDefault()->andReturnSelf();
        $mock->shouldReceive('error')->byDefault()->andReturnSelf();
        $mock->shouldReceive('warning')->byDefault()->andReturnSelf();
        $mock->shouldReceive('debug')->byDefault()->andReturnSelf();

        return $mock;
    }
}
