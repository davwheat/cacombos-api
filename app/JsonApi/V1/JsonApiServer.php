<?php

namespace App\JsonApi\V1;

use App\JsonApi\V1\Extensions\SearchByComponents\SearchByComponentsExtension;
use Illuminate\Support\Facades\Config;
use Psr\Http\Message\ServerRequestInterface;
use Tobyz\JsonApiServer\ErrorProviderInterface;
use Tobyz\JsonApiServer\Extension\Atomic;

class JsonApiServer
{
    protected ?\Tobyz\JsonApiServer\JsonApi $server = null;
    protected string $apiPath;

    /**
     * @param string $apiPath The path from `app.url` to the API with a leading slash, e.g. `"/v1/api"`
     */
    public function __construct(string $apiPath)
    {
        $this->apiPath = $apiPath;
    }

    public function requestHandler(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        if (!$this->server) {
            $this->server = new \Tobyz\JsonApiServer\JsonApi(Config::get('app.url').$this->apiPath);

            $this->addResources();

            $this->server->extension(new Atomic());
            $this->server->extension(new SearchByComponentsExtension());
        }

        if (!$request->hasHeader('Accept')) {
            $request = $request->withHeader('Accept', 'application/vnd.api+json');
        }

        /** @var Psr\Http\Message\ResponseInterface $response */
        try {
            $response = $this->server->handle($request);
        } catch (\Exception $e) {
            // Visualise errors in debug mode
            if (config('app.debug') && !($e instanceof ErrorProviderInterface)) {
                throw $e;
            }

            $response = $this->server->error($e);
        }

        return $response;
    }

    protected function addResources(): void
    {
        (new Resources($this->server))();
    }
}
