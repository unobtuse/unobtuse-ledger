<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify Plaid Webhook Signature Middleware
 * 
 * Validates that incoming webhook requests are authentic and from Plaid
 * by verifying the JWT signature in the Plaid-Verification header.
 * 
 * This is CRITICAL for production security - never disable in production.
 */
class VerifyPlaidWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip verification if disabled (for development/testing only)
        if (!config('plaid.webhook_verification_enabled', true)) {
            Log::warning('Plaid webhook verification is disabled - this should only be used in development');
            return $next($request);
        }

        $verificationHeader = $request->header('Plaid-Verification');

        if (!$verificationHeader) {
            Log::error('Plaid webhook missing verification header', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'error' => 'Missing verification header'
            ], 401);
        }

        try {
            // Decode JWT header without verification to get kid
            $tks = explode('.', $verificationHeader);
            if (count($tks) !== 3) {
                throw new \Exception('Invalid JWT format');
            }

            // Decode header (first part)
            $headerJson = base64_decode(strtr($tks[0], '-_', '+/'));
            $header = json_decode($headerJson, true);
            $kid = $header['kid'] ?? null;

            if (!$kid) {
                throw new \Exception('Missing kid in JWT header');
            }

            // Fetch Plaid's public keys
            $jwks = $this->fetchPlaidPublicKeys();

            if (empty($jwks)) {
                throw new \Exception('Failed to fetch Plaid public keys');
            }

            // Find the matching key
            $publicKey = $this->findPublicKey($jwks, $kid);

            if (!$publicKey) {
                throw new \Exception("Public key not found for kid: {$kid}");
            }

            // Verify the JWT signature
            $decoded = JWT::decode($verificationHeader, new Key($publicKey, 'RS256'));

            // Verify timestamp to prevent replay attacks (within 5 minutes)
            $now = time();
            $timestamp = $decoded->timestamp ?? null;

            if (!$timestamp) {
                throw new \Exception('Missing timestamp in webhook verification');
            }

            // Allow 5 minute window for clock skew
            $timeDiff = abs($now - $timestamp);
            if ($timeDiff > 300) {
                throw new \Exception("Webhook timestamp too old or too far in future: {$timeDiff} seconds");
            }

            // Store decoded verification data for use in controller
            $request->merge(['plaid_verification' => (array) $decoded]);

            Log::debug('Plaid webhook signature verified', [
                'kid' => $kid,
                'timestamp' => $timestamp,
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Plaid webhook verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'error' => 'Webhook verification failed'
            ], 401);
        }
    }

    /**
     * Fetch Plaid's public keys from JWK endpoint.
     *
     * @return array
     */
    protected function fetchPlaidPublicKeys(): array
    {
        $environment = config('plaid.environment', 'sandbox');
        $jwksUrl = config('plaid.webhook_verification_key_url');

        // If not configured, use default Plaid JWK URLs
        if (!$jwksUrl) {
            $jwksUrl = match($environment) {
                'production' => 'https://production.plaid.com/webhook_verification_key.jwk',
                'development' => 'https://development.plaid.com/webhook_verification_key.jwk',
                default => 'https://sandbox.plaid.com/webhook_verification_key.jwk',
            };
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($jwksUrl);
            $jwks = json_decode($response->getBody()->getContents(), true);

            return $jwks['keys'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Plaid public keys', [
                'url' => $jwksUrl,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Find the public key matching the kid.
     *
     * @param array $jwks
     * @param string $kid
     * @return string|null
     */
    protected function findPublicKey(array $jwks, string $kid): ?string
    {
        foreach ($jwks as $key) {
            if (($key['kid'] ?? null) === $kid) {
                try {
                    return JWK::parseKey($key)->toString();
                } catch (\Exception $e) {
                    Log::error('Failed to parse JWK', [
                        'kid' => $kid,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        }

        return null;
    }
}

