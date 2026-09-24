<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    /** @test Health endpoint returns 200 with standard envelope when all services are healthy. */
    public function test_health_endpoint_returns_200_when_healthy(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => ['status' => 'ok'],
        ]);
    }

    /** @test Health response includes all required envelope fields. */
    public function test_health_endpoint_has_standard_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'status',
                'service',
                'version',
                'environment',
                'timestamp',
                'checks' => [
                    'database' => ['status', 'driver'],
                    'storage' => ['status', 'default_disk', 'public_disk_writable'],
                    'cache' => ['status', 'driver'],
                ],
            ],
            'meta',
            'message',
        ]);
    }

    /** @test Health endpoint does not require authentication. */
    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/health')->assertStatus(200);
    }

    /** @test Health endpoint returns 503 when database is down. */
    public function test_health_endpoint_returns_503_on_db_failure(): void
    {
        DB::shouldReceive('connection->getPdo')->andThrow(new \RuntimeException('Connection refused'));

        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(503);
        $response->assertJson([
            'success' => false,
            'data' => ['status' => 'degraded'],
        ]);
    }
}
