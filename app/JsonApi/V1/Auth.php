<?php

namespace App\JsonApi\V1;

use App\RequiresAuthentication;
use Tobyz\JsonApiServer\Context;

class Auth
{
    static function uploaderOrAbove(Context $context): bool
    {
        $ra = new RequiresAuthentication(resolve(TokensRepository::class));

        return $ra($context->getRequest(), 'uploader');
    }

    static function adminOrAbove(Context $context): bool
    {
        $ra = new RequiresAuthentication(resolve(TokensRepository::class));

        return $ra($context->getRequest(), 'admin');
    }
}
