<?php

namespace App\JsonApi\V1\Resources;

use App\Models\NrComponent;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint;
use Tobyz\JsonApiServer\Laravel\EloquentResource;
use Tobyz\JsonApiServer\Laravel\Filter\Where;
use Tobyz\JsonApiServer\Resource\Creatable;
use Tobyz\JsonApiServer\Schema\Field;

class NrComponentsResource extends EloquentResource implements Creatable
{
    public function type(): string
    {
        return 'nr-components';
    }

    public function newModel(Context $context): object
    {
        return new NrComponent();
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
            Field\Str::make('dlClass'),
            Field\Str::make('ulClass'),
            Field\Integer::make('bandwidth'),
            Field\Boolean::make('supports90mhzBw'),
            Field\Integer::make('subcarrierSpacing'),
            Field\Integer::make('componentIndex'),

            Field\ToMany::make('dlMimos')->property('dl_mimos')->type('mimos')->includable()->withoutLinkage(),
            Field\ToMany::make('ulMimos')->property('ul_mimos')->type('mimos')->includable()->withoutLinkage(),

            Field\ToMany::make('dlModulations')->property('dl_modulations')->type('modulations')->includable()->withoutLinkage(),
            Field\ToMany::make('ulModulations')->property('ul_modulations')->type('modulations')->includable()->withoutLinkage(),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('band'),
        ];
    }

    public function sorts(): array
    {
        return [];
    }
}
