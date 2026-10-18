<?php

namespace App\Infrastructure\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LogicException;
use UnexpectedValueException;

final class AdminToken
{
    public function issue(int $userId): string
    {
        $now = time();

        return JWT::encode([
            'iss' => config('app.url'),
            'aud' => 'oficina-admin',
            'sub' => (string) $userId,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + (int) config('jwt.ttl'),
        ], $this->secret(), 'HS256');
    }

    public function userId(string $token): int
    {
        $claims = JWT::decode($token, new Key($this->secret(), 'HS256'));
        if (($claims->iss ?? null) !== config('app.url')
            || ($claims->aud ?? null) !== 'oficina-admin'
            || ! isset($claims->exp, $claims->sub, $claims->iat, $claims->nbf)
            || ! is_int($claims->exp)
            || ! is_string($claims->sub)
            || ! ctype_digit($claims->sub)
            || (int) $claims->sub < 1) {
            throw new UnexpectedValueException('Token inválido.');
        }

        return (int) $claims->sub;
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');
        if (strlen($secret) < 32) {
            throw new LogicException('Configure JWT_SECRET com pelo menos 32 caracteres.');
        }

        return $secret;
    }
}
