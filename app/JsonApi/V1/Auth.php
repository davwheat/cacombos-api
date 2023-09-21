<?php

namespace App\JsonApi\V1;

use App\Repositories\TokensRepository;
use App\RequiresAuthentication;
use Tobyz\JsonApiServer\Context;

class Auth
{
    public static function uploaderOrAbove(Context $context): bool
    {
        $ra = new RequiresAuthentication(resolve(TokensRepository::class));

        return $ra($context->request, 'uploader');
    }

    public static function adminOrAbove(Context $context): bool
    {
        $ra = new RequiresAuthentication(resolve(TokensRepository::class));

        return $ra($context->request, 'admin');
    }
}
