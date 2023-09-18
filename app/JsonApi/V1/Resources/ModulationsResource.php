<?php

namespace App\JsonApi\V1\Resources;

use App\Models\Modulation;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint;
use Tobyz\JsonApiServer\Laravel\EloquentResource;
use Tobyz\JsonApiServer\Laravel\Filter\Where;
use Tobyz\JsonApiServer\Resource\Creatable;
use Tobyz\JsonApiServer\Schema\Field;

class ModulationsResource extends EloquentResource implements Creatable
{
    public function type(): string
    {
        return 'modulations';
    }

    public function newModel(Context $context): object
    {
        return new Modulation();
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
            Field\Str::make('modulation'),
            Field\Boolean::make('isUl'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('modulation'),
            Where::make('isUl'),
        ];
    }

    public function sorts(): array
    {
        return [];
    }
}
