<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    /**
     * Health check endpoint for monitoring, load balancer probes and mobile clients.
     *
     * Returns 200 OK when critical services (database) are healthy.
     * Returns 503 Service Unavailable when a critical dependency is down.
     */
    public function index(): JsonResponse
    {
        $database = $this->checkDatabase();
        $storage = $this->checkStorage();
        $cache = $this->checkCache();

        // Database is the only hard-critical dependency for the API.
        $healthy = $database['status'] === 'ok';

        return response()->json([
            'success' => $healthy,
            'data' => [
                'status' => $healthy ? 'ok' : 'degraded',
                'service' => 'vbat-website-api',
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'timestamp' => now()->toIso8601String(),
                'checks' => [
                    'database' => $database,
                    'storage' => $storage,
                    'cache' => $cache,
                ],
            ],
            'meta' => null,
            'message' => $healthy ? 'Service healthy' : 'Service degraded',
        ], $healthy ? 200 : 503);
    }

    /**
     * Check database connectivity.
     *
     * @return array{status: string, driver: string, error?: string}
     */
    private function checkDatabase(): array
    {
        $driver = (string) config('database.default');

        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'driver' => $driver];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'driver' => $driver, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check that the default storage disk is writable.
     *
     * @return array{status: string, default_disk: string, public_disk_writable: bool, error?: string}
     */
    private function checkStorage(): array
    {
        $disk = (string) config('filesystems.default');
        $writable = false;

        try {
            $probe = 'health-check.txt';
            Storage::disk($disk)->put($probe, 'ok');
            $writable = Storage::disk($disk)->exists($probe);
            Storage::disk($disk)->delete($probe);
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'default_disk' => $disk,
                'public_disk_writable' => false,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'status' => $writable ? 'ok' : 'error',
            'default_disk' => $disk,
            'public_disk_writable' => $writable,
        ];
    }

    /**
     * Check cache read/write round-trip.
     *
     * @return array{status: string, driver: string, error?: string}
     */
    private function checkCache(): array
    {
        $driver = (string) config('cache.default');

        try {
            $key = 'health-check';
            Cache::put($key, 'ok', 5);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return ['status' => $ok ? 'ok' : 'error', 'driver' => $driver];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'driver' => $driver, 'error' => $e->getMessage()];
        }
    }
}
