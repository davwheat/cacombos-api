<?php

namespace App\JsonApi\V1;

use App\Models\{CapabilitySet, Combo, Device, DeviceFirmware, LteComponent, Modem, NrComponent};
use App\Repositories\TokensRepository;
use App\RequiresAuthentication;
use Tobyz\JsonApiServer\Adapter\EloquentAdapter;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Schema\Type;

class Resources
{
    protected \Tobyz\JsonApiServer\JsonApi $server;

    public function __construct(\Tobyz\JsonApiServer\JsonApi $server)
    {
        $this->server = $server;
    }

    public function __invoke()
    {
        $this->server->resourceType('devices', new EloquentAdapter(Device::class), function (Type $type) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('deviceName')->sortable();
            $type->attribute('modelName')->sortable();
            $type->attribute('manufacturer')->sortable();
            $type->attribute('releaseDate')->sortable();
            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasOne('modem')
                ->type('modems')
                ->includable()
                ->withoutLinkage()
                ->filterable();

            $type->hasMany('deviceFirmwares')
                ->type('device-firmwares')
                ->includable()
                ->withoutLinkage()
                ->includable();

            $type->defaultSort('-createdAt,manufacturer,deviceName');

            $type->creatable(RequiresAuthentication::class);
            $type->updatable(RequiresAuthentication::class);
        });

        $this->server->resourceType('modems', new EloquentAdapter(Modem::class), function (Type $type) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('name')->sortable();
            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasMany('devices')
                ->type('devices')
                ->includable()
                ->withoutLinkage()
                ->filterable();

            $type->creatable(RequiresAuthentication::class);
            $type->updatable(RequiresAuthentication::class);
        });

        $this->server->resourceType('device-firmwares', new EloquentAdapter(DeviceFirmware::class), function (Type $type) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('name')->sortable();
            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasMany('capabilitySets')
                ->type('capability-sets')
                ->includable()
                ->withoutLinkage()
                ->filterable();

            $type->creatable(RequiresAuthentication::class);
            $type->updatable(RequiresAuthentication::class);
        });

        $this->server->resourceType('capability-sets', new EloquentAdapter(CapabilitySet::class), function (Type $type) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('description')->sortable();
            $type->attribute('plmn')->sortable();

            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasOne('deviceFirmware')
                ->type('device-firmwares')
                ->includable()
                ->withoutLinkage()
                ->filterable();

            $type->hasMany('combos')
                ->type('combos')
                ->includable()
                ->withoutLinkage()
                ->filterable();

            $type->creatable(RequiresAuthentication::class);
            $type->updatable(RequiresAuthentication::class);
        });

        $this->server->resourceType('combos', new EloquentAdapter(Combo::class), function (Type $type) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('comboString')->filterable();
            $type->attribute('bandwidthCombinationSet');

            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasOne('capabilitySet')
                ->type('capability-sets')
                ->includable()
                ->withoutLinkage()
                ->filterable();

            $type->hasMany('lteComponents')
                ->type('lte-components')
                ->includable()
                ->withoutLinkage()
                // ->filterable()
            ;

            $type->hasMany('nrComponents')
                ->type('nr-components')
                ->includable()
                ->withoutLinkage()
                // ->filterable()
            ;
        });

        $this->server->resourceType('lte-components', new EloquentAdapter(LteComponent::class), function (Type $type) {
            $type->attribute('band')->filterable();

            $type->attribute('dlClass');
            $type->attribute('ulClass');
            $type->attribute('mimo');
            $type->attribute('ulMimo');
            $type->attribute('dlModulation');
            $type->attribute('ulModulation');

            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();
        });

        $this->server->resourceType('nr-components', new EloquentAdapter(NrComponent::class), function (Type $type) {
            $type->attribute('band')->filterable();

            $type->attribute('dlClass');
            $type->attribute('ulClass');
            $type->attribute('bandwidth');
            $type->attribute('subcarrierSpacing');
            $type->attribute('dlMimo');
            $type->attribute('ulMimo');
            $type->attribute('dlModulation');
            $type->attribute('ulModulation');

            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();
        });
    }
}
