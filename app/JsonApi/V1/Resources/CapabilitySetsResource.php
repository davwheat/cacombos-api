<?php

namespace App\JsonApi\V1\Resources;

use App\JsonApi\V1\Auth;
use App\Models\CapabilitySet;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint;
use Tobyz\JsonApiServer\Laravel\EloquentResource;
use Tobyz\JsonApiServer\Laravel\Filter\Where;
use Tobyz\JsonApiServer\Laravel\Sort\SortColumn;
use Tobyz\JsonApiServer\Resource\Creatable;
use Tobyz\JsonApiServer\Schema\Field;

use function Tobyz\JsonApiServer\Laravel\rules;

class CapabilitySetsResource extends EloquentResource implements Creatable
{
    public function type(): string
    {
        return 'capability-sets';
    }

    public function newModel(Context $context): object
    {
        return new CapabilitySet();
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make(),
            Endpoint\Index::make()->paginate(),
            Endpoint\Create::make()->visible(
                fn (Context $c) => Auth::uploaderOrAbove($c),
            ),
            Endpoint\Update::make()->visible(
                fn ($_, Context $c) => Auth::uploaderOrAbove($c),
            ),
            Endpoint\Delete::make()->visible(
                fn ($_, Context $c) => Auth::adminOrAbove($c),
            ),
        ];
    }

    public function fields(): array
    {
        return [
            Field\Str::make('uuid'),
            Field\Str::make('description')->required()->writable(),
            Field\Str::make('plmn')->nullable()->validate(rules(['regex:/^[0-9]{3}-[0-9]{2,3}$/']))->writable(),
            Field\Integer::make('lteCategoryDl')->nullable(),
            Field\Integer::make('lteCategoryUl')->nullable(),
            Field\Attribute::make('parserMetadata')->nullable(),
            Field\DateTime::make('createdAt'),
            Field\DateTime::make('updatedAt'),

            Field\ToOne::make('deviceFirmware')->property('device_firmware')->type('device-firmwares')->includable()->withoutLinkage()->writable(),
            Field\ToMany::make('combos')->type('combos')->includable()->withoutLinkage(),
            Field\ToMany::make('supportedNrBands')->property('supported_nr_bands')->type('supported-nr-bands')->includable()->withoutLinkage(),
            Field\ToMany::make('supportedLteBands')->property('supported_lte_bands')->type('supported-lte-bands')->includable()->withoutLinkage(),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('uuid'),
            Where::make('name'),
            Where::make('lteCategoryDl'),
            Where::make('lteCategoryUl'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('name'),
            SortColumn::make('createdAt'),
            SortColumn::make('lteCategoryDl'),
            SortColumn::make('lteCategoryUl'),
        ];
    }
}
