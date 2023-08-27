<?php

namespace App\DataParser;

use App\DataParser\ElementParser\MimoParser;
use App\DataParser\ElementParser\ModulationParser;
use App\Models\CapabilitySet;
use App\Models\SupportedLteBand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class LteSupportedBandsParser implements DataParser
{
    protected array $data;
    protected CapabilitySet $capabilitySet;

    protected MimoParser $mimoParser;
    protected ModulationParser $modulationParser;

    public function __construct(array $supportedBandsData, CapabilitySet $capabilitySet)
    {
        $this->data = $supportedBandsData;
        $this->capabilitySet = $capabilitySet;

        $this->mimoParser = new MimoParser();
        $this->modulationParser = new ModulationParser();
    }

    public function parseAndInsertAllModels(): void
    {
        $collection = new Collection();

        foreach ($this->data as $band) {
            $collection->push($this->parseSupportedLteBand($band));
        }

        $this->capabilitySet->supportedLteBands()->saveMany($collection);
    }

    protected function parseSupportedLteBand(array $band): SupportedLteBand
    {
        /** @var SupportedLteBand */
        $model = SupportedLteBand::firstOrCreate([
            'band'                    => Arr::get($band, 'band'),
            'power_class'             => Arr::get($band, 'powerClass'),
            'capability_set_id'       => $this->capabilitySet->id,
        ]);

        $model->mimos()->saveMany($this->getMimoModels($band));
        $model->modulations()->saveMany($this->getModulationModels($band));

        return $model;
    }

    protected function getMimoModels(array $band): Collection
    {
        $mimoDl = $this->mimoParser->getModelsFromData($band, 'mimoDl', false);
        $mimoUl = $this->mimoParser->getModelsFromData($band, 'mimoUl', true);

        return $mimoDl->merge($mimoUl);
    }

    protected function getModulationModels(array $band): Collection
    {
        $modDl = $this->modulationParser->getModelsFromData($band, 'modulationDl', false);
        $modUl = $this->modulationParser->getModelsFromData($band, 'modulationUl', true);

        return $modDl->merge($modUl);
    }
}
