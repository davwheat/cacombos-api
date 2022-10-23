<?php

namespace App\JsonApi;

use App\Models\{Device, Modem};
use Tobyz\JsonApiServer\Adapter\EloquentAdapter;
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
            $type->attribute('uuid');

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

            $type->hasMany('device-firmwares')
                ->type('device-firmwares')
                ->withoutLinkage()
                ->includable();

            $type->defaultSort('-createdAt,manufacturer,deviceName');
        });

        $this->server->resourceType('modems', new EloquentAdapter(Modem::class), function (Type $type) {
            $type->attribute('uuid');

            $type->attribute('modemName')->sortable();
            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasMany('devices')
                ->type('devices')
                ->withoutLinkage()
                ->filterable();
        });
    }
}
