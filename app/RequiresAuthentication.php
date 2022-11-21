<?php

namespace App;

use App\Repositories\TokensRepository;
use Tobyz\JsonApiServer\Context;

class RequiresAuthentication
{
    protected TokensRepository $tokens;

    public function __construct(TokensRepository $tokens)
    {
        $this->tokens = $tokens;
    }

    public function __invoke(Context $context): bool
    {
        $tokenArr = $context->getRequest()->getHeader('X-Auth-Token');
        $token = $tokenArr[0] ?? null;

        return $this->tokens->isValidToken($token);
    }
}
