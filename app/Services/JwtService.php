<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthenticationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * JWT Access Token generation and validation.
 * Uses HS256 by default; configure via .env JWT_ALGORITHM.
 */
final class JwtService
{
    private string $secret;
    private string $algorithm;
    private int    $accessTtl;
    private string $issuer;
    private string $audience;

    public function __construct(array $config)
    {
        $secret = $config['secret'] ?? '';
        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT_SECRET must be at least 32 characters long.');
        }

        $this->secret    = $secret;
        $this->algorithm = $config['algorithm'] ?? 'HS256';
        $this->accessTtl = (int) ($config['access_ttl'] ?? 900);
        $this->issuer    = $config['issuer']   ?? 'healingyuk-api';
        $this->audience  = $config['audience'] ?? 'healingyuk-clients';
    }

    /**
     * Generate a short-lived access token.
     */
    public function generateAccessToken(int $userId, string $email, string $role): string
    {
        $now = time();

        $payload = [
            'iss'   => $this->issuer,
            'aud'   => $this->audience,
            'iat'   => $now,
            'nbf'   => $now,
            'exp'   => $now + $this->accessTtl,
            'sub'   => (string) $userId,
            'email' => $email,
            'role'  => $role,
            'jti'   => $this->generateJti(),
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Validate and decode an access token.
     *
     * @return array<string, mixed>
     * @throws AuthenticationException
     */
    public function validateAccessToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            $payload = (array) $decoded;

            if (($payload['iss'] ?? '') !== $this->issuer) {
                throw new AuthenticationException('Invalid token issuer.');
            }

            return $payload;
        } catch (ExpiredException) {
            throw new AuthenticationException('Access token has expired.');
        } catch (SignatureInvalidException) {
            throw new AuthenticationException('Invalid token signature.');
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid access token: ' . $e->getMessage());
        }
    }

    /**
     * Generate a cryptographically secure refresh token (opaque string).
     */
    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * Hash a refresh token for safe DB storage.
     */
    public function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function getAccessTtl(): int
    {
        return $this->accessTtl;
    }

    private function generateJti(): string
    {
        return bin2hex(random_bytes(16));
    }
}
