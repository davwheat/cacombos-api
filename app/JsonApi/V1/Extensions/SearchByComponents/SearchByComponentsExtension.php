<?php

namespace App\JsonApi\V1\Extensions\SearchByComponents;

use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use JsonApiPhp\JsonApi as Structure;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Extension\Extension;
use Psr\Http\Message\ResponseInterface;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\Exception\MethodNotAllowedException;
use Tobyz\JsonApiServer\Serializer;

use function Tobyz\JsonApiServer\json_api_response;

class SearchByComponentsExtension extends Extension
{
    public function uri(): string
    {
        return 'http://mobilecombos.com/ext/search-by-components';
    }

    function queryToSQL($query)
    {
        $addSlashes = str_replace('?', "'?'", $query->toSql());

        $sql = str_replace('%', '#', $addSlashes);

        $sql = str_replace('?', '%s', $sql);

        $sql = vsprintf($sql, $query->getBindings());

        $sql = str_replace('#', '%', $sql);

        return $sql;
    }

    public function handle(Context $context): ?ResponseInterface
    {
        if (Arr::has(['/search-by-components'], $context->getPath())) {
            return null;
        }

        $request = $context->getRequest();

        if ($request->getMethod() !== 'POST') {
            throw new MethodNotAllowedException();
        }

        $body = $context->getBody();
        $searchQuery = $body['query'] ?? null;

        if (!isset($searchQuery)) {
            throw new BadRequestException('Missing combo search query');
        }

        $serializer = new Serializer($context);

        $query = Device::query();

        $query = $this->buildQuery($searchQuery, $query);

        // $query = $query->with([
        //     'deviceFirmwares',
        //     'deviceFirmwares.capabilitySets',
        //     'deviceFirmwares.capabilitySets.combos',
        //     'deviceFirmwares.capabilitySets.combos.nrComponents',
        //     'deviceFirmwares.capabilitySets.combos.lteComponents'
        // ]);

        dd($this->queryToSQL($query));

        $results = $query->get();

        foreach ($results as $device) {
            $serializer->add(
                $context->getApi()->getResourceType('devices'),
                $device,
                [
                    'deviceFirmwares' => [
                        'capabilitySets' => [
                            'combos' => [
                                'nrComponents' => [],
                                'lteComponents' => []
                            ]
                        ],
                    ],
                ]
            );
        }

        [$primary, $included] = $serializer->serialize();

        return json_api_response(new Structure\CompoundDocument(
            new Structure\ResourceCollection(...$primary),
            new Structure\Included(...$included),
            // new Structure\Link\SelfLink($this->buildUrl($context->getRequest())),
        ));
    }

    protected function hashQueryComponent(array $component): string
    {
        return md5(serialize($component));
    }

    protected function buildQuery(array $search, Builder $query): Builder
    {
        $searchWithQuantity = [];

        foreach ($search as $componentQuery) {
            $hash = $this->hashQueryComponent($componentQuery);

            if (!isset($searchWithQuantity[$hash])) {
                $searchWithQuantity[$hash] = [
                    'quantity' => 1,
                    'query' => $componentQuery
                ];
            } else {
                $searchWithQuantity[$hash]['quantity']++;
            }
        }

        foreach ($searchWithQuantity as $componentQuery) {
            $relation = 'deviceFirmwares.capabilitySets.combos.' .
                (strtolower(Arr::get($componentQuery, 'query.type')) === 'nr' ? 'nrComponents'
                    : 'lteComponents');

            $query = $query->whereHas($relation, function (Builder $query) use ($componentQuery) {
                $bands = Arr::get($componentQuery, 'query.bands');
                $dlClass = Arr::get($componentQuery, 'query.dlClass');
                $ulClass = Arr::get($componentQuery, 'query.ulClass');
                $mimo = Arr::get($componentQuery, 'query.mimo');
                $ul_mimo = Arr::get($componentQuery, 'query.ulMimo');
                $dl_modulation = Arr::get($componentQuery, 'query.dlModulation');
                $ul_modulation = Arr::get($componentQuery, 'query.ulModulation');

                if (isset($bands)) {
                    if (is_array($bands)) {
                        $query->whereIn('band', $bands);
                    } else {
                        $query->where('band', $bands);
                    }
                }

                if (isset($dlClass)) {
                    if (is_array($dlClass)) {
                        $query->whereIn('dl_class', $dlClass);
                    } else {
                        $query->where('dl_class', $dlClass);
                    }
                }

                if (isset($ulClass)) {
                    if (is_array($ulClass)) {
                        $query->whereIn('ul_class', $ulClass);
                    } else {
                        $query->where('ul_class', $ulClass);
                    }
                }

                if (isset($mimo)) {
                    if (is_array($mimo)) {
                        $query->whereIn('mimo', $mimo);
                    } else {
                        $query->where('mimo', $mimo);
                    }
                }

                if (isset($ul_mimo)) {
                    if (is_array($ul_mimo)) {
                        $query->whereIn('ul_mimo', $ul_mimo);
                    } else {
                        $query->where('ul_mimo', $ul_mimo);
                    }
                }

                if (isset($dl_modulation)) {
                    if (is_array($dl_modulation)) {
                        $query->whereIn('dl_modulation', $dl_modulation);
                    } else {
                        $query->where('dl_modulation', $dl_modulation);
                    }
                }

                if (isset($ul_modulation)) {
                    if (is_array($ul_modulation)) {
                        $query->whereIn('ul_modulation', $ul_modulation);
                    } else {
                        $query->where('ul_modulation', $ul_modulation);
                    }
                }

                return $query;
            }, '=', $componentQuery['quantity']);
        }

        return $query;
    }
}
