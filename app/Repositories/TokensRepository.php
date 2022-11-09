<?php

namespace App\Repositories;

use App\Models\Token;

class TokensRepository {
    public function isValidToken(string $token) {
        return Token::query()
            ->where('token', '=', $token)
            ->where('expires_after', '>', now())
            ->exists();
    }
    
    public function assertValidToken(string $token) {
        $isValid = $this->isValidToken($token);

        if (!$isValid) {
            throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('No token or invalid token provided.');
        }
    }
}