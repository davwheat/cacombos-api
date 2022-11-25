<?php

namespace App;

use App\Repositories\TokensRepository;
use Psr\Http\Message\ServerRequestInterface;

class RequiresAuthentication
{
    protected TokensRepository $tokens;

    public function __construct(TokensRepository $tokens)
    {
        $this->tokens = $tokens;
    }

    public function __invoke(ServerRequestInterface $request, string $type = "admin", bool $assert = false): bool
    {
        $tokenArr = $request->getHeader('X-Auth-Token');
        $token = $tokenArr[0] ?? null;

        $type = strtolower($type);
        // replace underscores and dashes with spaces
        $type = str_replace(['_', '-'], ' ', $type);
        // capitalize each word
        $type = ucwords($type);
        // remove spaces
        $type = str_replace(' ', '', $type);

        $methodName = "Valid{$type}Token";
        $methodName = ($assert ? 'assert' : 'is') . $methodName;

        $result = $this->tokens->$methodName($token);

        return $result;
    }
}
