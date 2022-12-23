<?php

namespace App\Repositories;

use App\Models\Token;

class TokensRepository
{
    protected const TOKEN_RANK = [
        'parser' => 25,
        'uploader' => 50,
        'admin' => 100,
    ];

    protected function queryToken(string $token)
    {
        return Token::query()
            ->where('token', $token)
            ->where('expires_after', '>', now());
    }

    protected function failsAssert()
    {
        throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('No token or invalid token provided.');
    }

    protected function isTokenValidFor(string $token, string $tokenType): bool
    {
        $tokenRank = self::TOKEN_RANK[$tokenType] ?? 0;
        $tokenObj = $this->queryToken($token)->first();

        if ($tokenObj === null) {
            return false;
        }

        return (self::TOKEN_RANK[$tokenObj->type] ?? -1) >= $tokenRank;
    }

    protected function assertTokenValidFor(string $token, string $tokenType): void
    {
        if (!$this->isTokenValidFor($token, $tokenType)) {
            $this->failsAssert();
        }
    }

    public function isValidParserToken(string $token): bool
    {
        return $this->isTokenValidFor($token, 'parser');
    }

    public function assertValidParserToken(string $token): void
    {
        $this->assertTokenValidFor($token, 'parser');
    }

    public function isValidUploaderToken(string $token): bool
    {
        return $this->isTokenValidFor($token, 'uploader');
    }

    public function assertValidUploaderToken(string $token): void
    {
        $this->assertTokenValidFor($token, 'uploader');
    }

    public function isValidAdminToken(string $token): bool
    {
        return $this->isTokenValidFor($token, 'admin');
    }

    public function assertValidAdminToken(string $token): void
    {
        $this->assertTokenValidFor($token, 'admin');
    }
}
