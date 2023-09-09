<?php

namespace App\JsonApi\V1\Resources;

use App\JsonApi\V1\Auth;
use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\DeviceFirmware;
use App\Models\LteComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Laravel\EloquentResource;
use Tobyz\JsonApiServer\Schema\Field;
use Tobyz\JsonApiServer\Endpoint;
use Tobyz\JsonApiServer\Laravel\Filter\Scope;
use Tobyz\JsonApiServer\Laravel\Filter\Where;
use Tobyz\JsonApiServer\Laravel\Sort\SortColumn;
use Tobyz\JsonApiServer\Resource\Creatable;
use Tobyz\JsonApiServer\Schema\Sort;

use function Tobyz\JsonApiServer\Laravel\rules;

class LteComponentsResource extends EloquentResource implements Creatable
{
    public function type(): string
    {
        return 'lte-components';
    }

    public function newModel(Context $context): object
    {
        return new LteComponent();
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
            Field\Integer::make('componentIndex'),

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
        ];
    }

    public function sorts(): array
    {
        return [];
    }
}
