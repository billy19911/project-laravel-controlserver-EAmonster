<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenOrEaKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = (string) $request->bearerToken();
        if ($bearer !== '') {
            $tokenHash = hash('sha256', $bearer);

            $user = User::query()
                ->where('api_token_hash', $tokenHash)
                ->first();

            if ($user !== null) {
                $request->setUserResolver(static fn (): User => $user);

                return $next($request);
            }
        }

        $apiKey = (string) $request->header('X-EA-KEY', $request->header('X-API-Key', ''));
        $expectedApiKey = (string) config('services.ea.api_key', '');

        if ($expectedApiKey !== '' && hash_equals($expectedApiKey, $apiKey)) {
            return $next($request);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Unauthorized. Invalid token.',
        ], 401);
    }
}