<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EaRequestSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = (string) $request->header('X-EA-KEY', $request->header('X-API-Key', ''));
        $expectedApiKey = (string) config('services.ea.api_key', '');

        if ($expectedApiKey === '' || !hash_equals($expectedApiKey, $apiKey)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unauthorized EA key.',
            ], 401);
        }

        $requiresSignature = (bool) config('services.ea.require_signature', false);
        if (!$requiresSignature) {
            return $next($request);
        }

        $signatureSecret = (string) config('services.ea.signature_secret', '');
        if ($signatureSecret === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Server signature secret is not configured.',
            ], 500);
        }

        $timestamp = (string) $request->header('X-EA-TIMESTAMP', '');
        $signature = (string) $request->header('X-EA-SIGNATURE', '');
        $accountId = (string) ($request->input('account_id', $request->query('account_id', '')));

        if ($timestamp === '' || $signature === '' || $accountId === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing EA signature headers.',
            ], 401);
        }

        if (!ctype_digit($timestamp)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid timestamp format.',
            ], 401);
        }

        $ts = (int) $timestamp;
        $maxSkew = (int) config('services.ea.signature_ttl_seconds', 300);
        if (abs(time() - $ts) > $maxSkew) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Signature timestamp expired.',
            ], 401);
        }

        $base = strtoupper($request->method())
            . '|' . $request->path()
            . '|' . $accountId
            . '|' . $timestamp;

        $expectedSignature = hash_hmac('sha256', $base, $signatureSecret);
        if (!hash_equals($expectedSignature, $signature)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid EA signature.',
            ], 401);
        }

        return $next($request);
    }
}
