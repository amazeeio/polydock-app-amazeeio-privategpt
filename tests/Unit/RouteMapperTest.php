<?php

declare(strict_types=1);

namespace Tests\Unit;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Routemap\Routemapper;
use PHPUnit\Framework\TestCase;

class RouteMapperTest extends TestCase
{
    public function test_cluster_map_returns_correct_cluster_id(): void
    {
        $this->assertEquals('ch4', Routemapper::clusterMap(131));
        $this->assertEquals('de3', Routemapper::clusterMap(115));
        $this->assertEquals('au2', Routemapper::clusterMap(132));
        $this->assertEquals('us2', Routemapper::clusterMap(126));
        $this->assertEquals('uk3', Routemapper::clusterMap(122));
    }

    public function test_cluster_map_throws_on_invalid_target(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Routemapper::clusterMap(999);
    }

    public function test_deploy_target_to_routes_returns_expected_routes(): void
    {
        $routes = Routemapper::deployTargetToRoutes(131, 'myproject');
        $expected = [
            'routes' => [
                [
                    'domain' => 'myproject.login.ch4.private.amazee.ai',
                    'service' => 'nginx',
                ],
                [
                    'domain' => 'myproject.ch4.private.amazee.ai',
                    'service' => 'chat',
                ],
            ],
        ];
        $this->assertEquals($expected, $routes);
    }

    public function test_base64encoded_routes_returns_valid_base64_json(): void
    {
        $encoded = Routemapper::base64encodedRoutes(131, 'myproject');
        $decoded = json_decode(base64_decode($encoded), true);
        $expected = [
            'routes' => [
                [
                    'domain' => 'myproject.login.ch4.private.amazee.ai',
                    'service' => 'nginx',
                ],
                [
                    'domain' => 'myproject.ch4.private.amazee.ai',
                    'service' => 'chat',
                ],
            ],
        ];
        $this->assertEquals($expected, $decoded);
    }
}
