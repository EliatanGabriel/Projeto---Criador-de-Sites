<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceProfiler
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! env('PERF_PROFILER', false)) {
            return $next($request);
        }

        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $middlewareStart = microtime(true);
        $queryCount = 0;
        $queryTime = 0.0;
        $firstQueryAt = null;
        $lastQueryAt = null;

        $listener = function ($query) use (&$queryCount, &$queryTime, &$firstQueryAt, &$lastQueryAt) {
            $queryCount++;
            $queryTime += $query->time;
            $now = microtime(true);
            if ($firstQueryAt === null) {
                $firstQueryAt = $now;
            }
            $lastQueryAt = $now;
            if ($queryCount <= 5 || $query->time > 20) {
                Log::info('[perf-sql]', [
                    'request_id' => request()->headers->get('X-Request-Id', 'n/a'),
                    'time_ms' => round($query->time, 2),
                    'sql' => substr($query->sql, 0, 200),
                    'bindings_count' => count($query->bindings),
                ]);
            }
        };

        DB::listen($listener);
        $response = null;
        try {
            $response = $next($request);
        } finally {
            $end = microtime(true);
            $bootstrapMs = ($middlewareStart - $start) * 1000;
            $middlewareMs = ($end - $middlewareStart) * 1000;
            $totalMs = ($end - $start) * 1000;
            $serverMs = $totalMs - ($firstQueryAt ? ($firstQueryAt - $start) * 1000 : 0);

            $payload = [
                'request_id' => $request->headers->get('X-Request-Id', 'n/a'),
                'method' => $request->method(),
                'path' => $request->path(),
                'bootstrap_ms' => round($bootstrapMs, 2),
                'middleware_ms' => round($middlewareMs, 2),
                'total_ms' => round($totalMs, 2),
                'sql_count' => $queryCount,
                'sql_ms' => round($queryTime, 2),
                'first_query_ms' => $firstQueryAt ? round(($firstQueryAt - $start) * 1000, 2) : null,
                'config' => [
                    'session_driver' => config('session.driver'),
                    'session_cookie' => config('session.cookie'),
                    'database_default' => config('database.default'),
                    'app_env' => config('app.env'),
                    'app_name' => config('app.name'),
                    'cache_default' => config('cache.default'),
                ],
            ];
            Log::info('[perf]', $payload);
            if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
                $response->headers->set('Server-Timing', sprintf(
                    'bootstrap;dur=%.1f, middleware;dur=%.1f, total;dur=%.1f',
                    $bootstrapMs,
                    $middlewareMs,
                    $totalMs
                ));
                $response->headers->set('X-Perf-Json', base64_encode(json_encode($payload)));
            }
        }

        return $response;
    }
}
