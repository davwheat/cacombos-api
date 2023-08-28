<?php

namespace App\JsonApi\V1;

use App\Models\{CapabilitySet, Combo, Device, DeviceFirmware, LteComponent, Mimo, Modem, Modulation, NrBand, NrComponent, SupportedNrBand};
use App\Repositories\TokensRepository;
use App\RequiresAuthentication;
use BeyondCode\ServerTiming\Facades\ServerTiming;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tobyz\JsonApiServer\Adapter\EloquentAdapter;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Schema\Type;

class Resources
{
    protected \Tobyz\JsonApiServer\JsonApi $server;

    protected RequiresAuthentication $requiresAuthentication;

    public function __construct(\Tobyz\JsonApiServer\JsonApi $server)
    {
        $this->server = $server;
        $this->requiresAuthentication = new RequiresAuthentication(resolve(TokensRepository::class));
    }

    public function __invoke()
    {
        ServerTiming::start('Registering JSON:API models');

        $uploaderOnlyCreate = function (Context $context): bool {
            return ($this->requiresAuthentication)($context->getRequest(), 'uploader');
        };
        $uploaderOnlyUpdate = function ($model, Context $context): bool {
            return ($this->requiresAuthentication)($context->getRequest(), 'uploader');
        };
        $adminOnlyDelete = function ($model, Context $context): bool {
            return ($this->requiresAuthentication)($context->getRequest(), 'admin');
        };

        $this->server->resourceType('devices', new EloquentAdapter(Device::class), function (Type $type) use ($uploaderOnlyCreate, $uploaderOnlyUpdate, $adminOnlyDelete) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('deviceName')->sortable()->writable();
            $type->attribute('modelName')->sortable()->writable();
            $type->attribute('manufacturer')->sortable()->writable();
            $type->attribute('releaseDate')->sortable()->writable();
            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasOne('modem')
                ->type('modems')
                ->includable()
                ->withoutLinkage()
                ->filterable()
                ->writable();

            $type->hasMany('deviceFirmwares')
                ->type('device-firmwares')
                ->includable()
                ->withoutLinkage()
                ->includable()
                ->writable();

            $type->defaultSort('-createdAt,manufacturer,deviceName');

            $type->creatable($uploaderOnlyCreate);
            $type->updatable($uploaderOnlyUpdate);
            $type->deletable($adminOnlyDelete);

            $type->filter('deviceFullName', function (Builder $query, $value, Context $context) {
                $query->where(DB::raw('CONCAT_WS(" ", devices.manufacturer, devices.device_name, devices.model_name)'), 'LIKE', "%$value%");
            });
        });

        $this->server->resourceType('modems', new EloquentAdapter(Modem::class), function (Type $type) use ($uploaderOnlyCreate, $uploaderOnlyUpdate, $adminOnlyDelete) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('name')->sortable()->writable();
            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasMany('devices')
                ->type('devices')
                ->includable()
                ->withoutLinkage()
                ->filterable()
                ->writable();

            $type->dontPaginate();

            $type->creatable($uploaderOnlyCreate);
            $type->updatable($uploaderOnlyUpdate);
            $type->deletable($adminOnlyDelete);
        });

        $this->server->resourceType('device-firmwares', new EloquentAdapter(DeviceFirmware::class), function (Type $type) use ($uploaderOnlyCreate, $uploaderOnlyUpdate, $adminOnlyDelete) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('name')->sortable()->writable();
            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasOne('device')
                ->type('devices')
                // ->includable()
                ->withoutLinkage()
                // ->filterable()
                ->writable();

            $type->hasMany('capabilitySets')
                ->type('capability-sets')
                ->includable()
                ->withoutLinkage()
                ->filterable()
                ->writable();

            $type->creatable($uploaderOnlyCreate);
            $type->updatable($uploaderOnlyUpdate);
            $type->deletable($adminOnlyDelete);
        });

        $this->server->resourceType('capability-sets', new EloquentAdapter(CapabilitySet::class), function (Type $type) use ($uploaderOnlyCreate, $uploaderOnlyUpdate, $adminOnlyDelete) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('description')->sortable()->writable();
            $type->attribute('plmn')->sortable()->writable()
                ->validate(function (callable $fail, $value, $model, Context $context) {
                    $validator = Validator::make(['plmn' => $value], [
                        'plmn' => ['nullable', 'regex:/^[0-9]{3}-[0-9]{2,3}$/'],
                    ]);

                    if ($validator->fails()) {
                        $fail($validator->errors()->first('plmn'));
                    }
                });

            $type->attribute('lteCategoryDl');
            $type->attribute('lteCategoryUl');

            $type->attribute('parserMetadata');

            $type->attribute('createdAt')->sortable();
            $type->attribute('updatedAt')->sortable();

            $type->hasOne('deviceFirmware')
                ->type('device-firmwares')
                ->includable()
                ->withoutLinkage()
                ->filterable()
                ->writable();

            $type->hasMany('combos')
                ->type('combos')
                ->includable()
                ->withoutLinkage()
                ->filterable()
                ->writable();

            $type->hasMany('supportedNrBands')
                ->type('supportedNrBands')
                ->includable()
                ->withoutLinkage();

            $type->hasMany('supportedLteBands')
                ->type('supportedLteBands')
                ->includable()
                ->withoutLinkage();

            $type->creatable($uploaderOnlyCreate);
            $type->updatable($uploaderOnlyUpdate);
            $type->deletable($adminOnlyDelete);
        });

        $this->server->resourceType('combos', new EloquentAdapter(Combo::class), function (Type $type) {
            $type->attribute('uuid')
                ->filterable();

            $type->attribute('comboString')->filterable();
            $type->attribute('bandwidthCombinationSetEutra');
            $type->attribute('bandwidthCombinationSetNr');
            $type->attribute('bandwidthCombinationSetIntraEndc');

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
                ->withoutLinkage();
            // ->filterable()

            $type->hasMany('nrComponents')
                ->type('nr-components')
                ->includable()
                ->withoutLinkage();
            // ->filterable()
        });

        $this->server->resourceType('lte-components', new EloquentAdapter(LteComponent::class), function (Type $type) {
            $type->attribute('band')->filterable();

            $type->attribute('dlClass');
            $type->attribute('ulClass');
            $type->attribute('componentIndex');

            $type->hasMany('dlMimos')
                ->type('mimos')
                ->includable()
                ->withoutLinkage();
            $type->hasMany('ulMimos')
                ->type('mimos')
                ->includable()
                ->withoutLinkage();

            $type->hasMany('dlModulations')
                ->type('modulations')
                ->includable()
                ->withoutLinkage();
            $type->hasMany('ulModulations')
                ->type('modulations')
                ->includable()
                ->withoutLinkage();
        });

        $this->server->resourceType('nr-components', new EloquentAdapter(NrComponent::class), function (Type $type) {
            $type->attribute('band')->filterable();

            $type->attribute('dlClass');
            $type->attribute('ulClass');
            $type->attribute('bandwidth');
            $type->attribute('supports90mhzBw');
            $type->attribute('subcarrierSpacing');
            $type->attribute('componentIndex');

            $type->hasMany('dlMimos')
                ->type('mimos')
                ->includable()
                ->withoutLinkage();
            $type->hasMany('ulMimos')
                ->type('mimos')
                ->includable()
                ->withoutLinkage();

            $type->hasMany('dlModulations')
                ->type('modulations')
                ->includable()
                ->withoutLinkage();
            $type->hasMany('ulModulations')
                ->type('modulations')
                ->includable()
                ->withoutLinkage();
        });

        $this->server->resourceType('mimos', new EloquentAdapter(Mimo::class), function (Type $type) {
            $type->attribute('id')->filterable();
            $type->attribute('mimo')->filterable();
            $type->attribute('isUl')->filterable();
        });

        $this->server->resourceType('modulations', new EloquentAdapter(Modulation::class), function (Type $type) {
            $type->attribute('id')->filterable();
            $type->attribute('modulation')->filterable();
            $type->attribute('isUl')->filterable();
        });

        $this->server->resourceType('supported-nr-bands', new EloquentAdapter(SupportedNrBand::class), function (Type $type) {
            $type->attribute('id')->filterable();
            $type->attribute('band')->filterable();
            $type->attribute('rateMatchingLteCrs');
            $type->attribute('powerClass');
            $type->attribute('maxUplinkDutyCycle');
            $type->attribute('bandwidths');
            $type->attribute('supportsEndc');
            $type->attribute('supportsSa');

            $type->hasMany('dlMimos')
                ->type('mimos')
                ->includable()
                ->withoutLinkage();
            $type->hasMany('ulMimos')
                ->type('mimos')
                ->includable()
                ->withoutLinkage();

            $type->hasMany('dlModulations')
                ->type('modulations')
                ->includable()
                ->withoutLinkage();
            $type->hasMany('ulModulations')
                ->type('modulations')
                ->includable()
                ->withoutLinkage();
        });

        $this->server->resourceType('supported-lte-bands', new EloquentAdapter(SupportedNrBand::class), function (Type $type) {
            $type->attribute('id')->filterable();
            $type->attribute('band')->filterable();
            $type->attribute('powerClass');

            $type->hasMany('dlMimos')
                ->type('mimos')
                ->includable()
                ->withoutLinkage();
            $type->hasMany('ulMimos')
                ->type('mimos')
                ->includable()
                ->withoutLinkage();

            $type->hasMany('dlModulations')
                ->type('modulations')
                ->includable()
                ->withoutLinkage();
            $type->hasMany('ulModulations')
                ->type('modulations')
                ->includable()
                ->withoutLinkage();
        });

        ServerTiming::stop('Registering JSON:API models');
    }
}
