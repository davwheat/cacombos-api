<?php

namespace App\JsonApi\V1;

use App\Models\{CapabilitySet, Combo, Device, DeviceFirmware, LteComponent, Mimo, Modem, Modulation, NrBand, NrComponent, SupportedNrBand};
use App\Repositories\TokensRepository;
use App\RequiresAuthentication;
use BeyondCode\ServerTiming\Facades\ServerTiming;

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

        $this->server->resource(new Resources\CapabilitySetsResource());
        $this->server->resource(new Resources\CombosResource());
        $this->server->resource(new Resources\DeviceFirmwaresResource());
        $this->server->resource(new Resources\DevicesResource());
        $this->server->resource(new Resources\LteComponentsResource());
        $this->server->resource(new Resources\MimosResource());
        $this->server->resource(new Resources\ModemsResource());
        $this->server->resource(new Resources\ModulationsResource());
        $this->server->resource(new Resources\NrComponentsResource());
        $this->server->resource(new Resources\SupportedLteBandsResource());
        $this->server->resource(new Resources\SupportedNrBandsResource());

        ServerTiming::stop('Registering JSON:API models');
    }
}
