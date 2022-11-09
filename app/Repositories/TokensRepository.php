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
}