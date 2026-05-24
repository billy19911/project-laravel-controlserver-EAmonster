<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EaAccountRateLimit
{
    public function __construct(private readonly RateLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $accountId = (string) ($request->input('account_id', $request->query('account_id', 'unknown')));
        $ip = (string) $request->ip();
        $maxAttempts = (int) config('services.ea.rate_limit_per_minute', 120);
        $decaySeconds = 60;

        $key = 'ea-rate:' . sha1($accountId . '|' . $ip);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Too many EA requests. Slow down.',
                'retry_after' => $this->limiter->availableIn($key),
            ], 429);
        }

        $this->limiter->hit($key, $decaySeconds);

        return $next($request);
    }
}
