<?php

namespace App\JsonApi\V1\Resources;

use App\Models\Mimo;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint;
use Tobyz\JsonApiServer\Laravel\EloquentResource;
use Tobyz\JsonApiServer\Laravel\Filter\Where;
use Tobyz\JsonApiServer\Resource\Creatable;
use Tobyz\JsonApiServer\Schema\Field;

class MimosResource extends EloquentResource implements Creatable
{
    public function type(): string
    {
        return 'mimos';
    }

    public function newModel(Context $context): object
    {
        return new Mimo();
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make(),
            Endpoint\Index::make(),
        ];
    }

    public function fields(): array
    {
        return [
            Field\Integer::make('mimo'),
            Field\Boolean::make('isUl'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('mimo'),
            Where::make('isUl'),
        ];
    }

    public function sorts(): array
    {
        return [];
    }
}
