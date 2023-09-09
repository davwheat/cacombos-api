<?php

namespace App\JsonApi\V1\Resources;

use App\Models\SupportedLteBand;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint;
use Tobyz\JsonApiServer\Laravel\EloquentResource;
use Tobyz\JsonApiServer\Laravel\Filter\Where;
use Tobyz\JsonApiServer\Resource\Creatable;
use Tobyz\JsonApiServer\Schema\Field;

class SupportedLteBandsResource extends EloquentResource implements Creatable
{
    public function type(): string
    {
        return 'supported-lte-bands';
    }

    public function newModel(Context $context): object
    {
        return new SupportedLteBand();
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make(),
            // Endpoint\Index::make(),
        ];
    }

    public function fields(): array
    {
        return [
            Field\Integer::make('band'),
            Field\Str::make('powerClass'),

            Field\ToMany::make('dlMimos')->type('mimos')->includable()->withoutLinkage(),
            Field\ToMany::make('ulMimos')->type('mimos')->includable()->withoutLinkage(),

            Field\ToMany::make('dlModulations')->type('modulations')->includable()->withoutLinkage(),
            Field\ToMany::make('ulModulations')->type('modulations')->includable()->withoutLinkage(),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('band'),
            Where::make('powerClass'),
        ];
    }

    public function sorts(): array
    {
        return [];
    }
}
