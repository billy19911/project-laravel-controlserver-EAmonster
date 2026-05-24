<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = (string) $request->bearerToken();

        if ($bearer === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unauthorized. Missing Bearer token.',
            ], 401);
        }

        $tokenHash = hash('sha256', $bearer);

        $user = User::query()
            ->where('api_token_hash', $tokenHash)
            ->first();

        if ($user === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unauthorized. Invalid token.',
            ], 401);
        }

        $request->setUserResolver(static fn (): User => $user);

        return $next($request);
    }
}
