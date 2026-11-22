<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckController extends Controller
{
    /**
     * Check application and database health status (no environment details are exposed).
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $databaseConnected = true;
        } catch (Throwable $exception) {
            report($exception);
            $databaseConnected = false;
        }

        return response()->json([
            'status' => $databaseConnected ? 'healthy' : 'degraded',
            'app' => 'KCAM',
            'database' => $databaseConnected ? 'connected' : 'disconnected',
            'timestamp' => now()->toIso8601String(),
        ], $databaseConnected ? 200 : 503);
    }
}
