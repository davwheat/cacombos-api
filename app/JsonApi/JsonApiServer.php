<?php

namespace App\JsonApi;

use App\Models\Device;
use App\Models\Modem;
use Illuminate\Support\Facades\Config;
use Psr\Http\Message\ServerRequestInterface;
use Tobyz\JsonApiServer\Schema\Type;
use Tobyz\JsonApiServer\Adapter\EloquentAdapter;

class JsonApiServer
{
    protected ?\Tobyz\JsonApiServer\JsonApi $server = null;

    public function requestHandler(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        if (!$this->server) {
            $this->server = new \Tobyz\JsonApiServer\JsonApi(Config::get('app.url'));

            $this->addResources();
        }

        if (!$request->hasHeader('Accept')) {
            $request = $request->withHeader('Accept', 'application/vnd.api+json');
        }

        /** @var Psr\Http\Message\ResponseInterface $response */
        try {
            $response = $this->server->handle($request);
        } catch (\Exception $e) {
            // Visualise errors in debug mode
            if (config('app.debug')) throw $e;

            $response = $this->server->error($e);
        }

        return $response;
    }

    protected function addResources(): void
    {
        (new Resources($this->server))();
    }
}
