<?php

namespace App\JsonApi\V1\Resources;

use App\JsonApi\V1\Auth;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Laravel\EloquentResource;
use Tobyz\JsonApiServer\Schema\Field;
use Tobyz\JsonApiServer\Endpoint;
use Tobyz\JsonApiServer\Laravel\Filter\Scope;
use Tobyz\JsonApiServer\Laravel\Filter\Where;
use Tobyz\JsonApiServer\Laravel\Filter\WhereHas;
use Tobyz\JsonApiServer\Laravel\Sort\SortColumn;
use Tobyz\JsonApiServer\Resource\Creatable;

class DevicesResource extends EloquentResource
{
    public function type(): string
    {
        return 'devices';
    }

    public function newModel(Context $context): object
    {
        return new Device();
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make(),
            Endpoint\Index::make()->defaultSort('-createdAt,manufacturer,deviceName')->paginate(20, 100),
            Endpoint\Create::make()->visible(
                fn (Context $c) => Auth::uploaderOrAbove($c),
            ),
            Endpoint\Update::make()->visible(
                fn (Context $c) => Auth::uploaderOrAbove($c),
            ),
            Endpoint\Delete::make()->visible(
                fn (Context $c) => Auth::adminOrAbove($c),
            ),
        ];
    }

    public function fields(): array
    {
        return [
            Field\Str::make('uuid'),
            Field\Str::make('deviceName')->required(),
            Field\Str::make('modelName')->required(),
            Field\Str::make('manufacturer')->required(),
            Field\Date::make('releaseDate')->required(),
            Field\DateTime::make('createdAt'),
            Field\DateTime::make('updatedAt'),

            Field\ToOne::make('modem')->type('modems')->includable()->withoutLinkage()->writable()->required(),
            Field\ToMany::make('deviceFirmwares')->type('device-firmwares')->includable()->withoutLinkage()->writable(),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('uuid'),
            Where::make('deviceName'),
            Where::make('modelName'),
            Where::make('manufacturer'),
            Where::make('releaseDate'),
            WhereHas::make('modem'),
            Scope::make('deviceFullName')->scope(fn (
                $query,
                string|array $value,
                Context $context,
            ) => $query->where(DB::raw('CONCAT_WS(" ", devices.manufacturer, devices.device_name, devices.model_name)'), 'LIKE', "%$value%"))
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
            SortColumn::make('releaseDate'),
            SortColumn::make('manufacturer'),
            SortColumn::make('deviceName'),
        ];
    }
}
