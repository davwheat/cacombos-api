<?php

namespace App\JsonApi\V1\Resources;

use App\JsonApi\V1\Auth;
use App\Models\Modem;
use Illuminate\Support\Facades\DB;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Laravel\EloquentResource;
use Tobyz\JsonApiServer\Schema\Field;
use Tobyz\JsonApiServer\Endpoint;
use Tobyz\JsonApiServer\Laravel\Filter\Scope;
use Tobyz\JsonApiServer\Laravel\Filter\Where;
use Tobyz\JsonApiServer\Laravel\Sort\SortColumn;
use Tobyz\JsonApiServer\Resource\Creatable;
use Tobyz\JsonApiServer\Schema\Sort;

class ModemsResource extends EloquentResource implements Creatable
{
    public function type(): string
    {
        return 'modems';
    }

    public function newModel(Context $context): object
    {
        return new Modem();
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make(),
            Endpoint\Index::make()->defaultSort('name,-createdAt')->paginate(),
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
            Field\Str::make('name')->required(),
            Field\DateTime::make('createdAt'),
            Field\DateTime::make('updatedAt'),

            Field\ToMany::make('devices')->type('devices')->includable()->withoutLinkage()->writable(),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('uuid'),
            Where::make('name'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('name'),
            SortColumn::make('createdAt'),
        ];
    }
}
