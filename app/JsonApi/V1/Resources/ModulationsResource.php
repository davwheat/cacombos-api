<?php

namespace App\JsonApi\V1\Resources;

use App\JsonApi\V1\Auth;
use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\DeviceFirmware;
use App\Models\LteComponent;
use App\Models\Mimo;
use App\Models\Modulation;
use App\Models\NrComponent;
use FFI;
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
