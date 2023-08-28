<?php

namespace App\DataParser;

use App\DataParser\ElementParser\MimoParser;
use App\DataParser\ElementParser\ModulationParser;
use App\Models\CapabilitySet;
use App\Models\SupportedNrBand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class NrSupportedBandsParser implements DataParser
{
    protected ?array $nrBands;
    protected ?array $nrNsaBandsEutra;
    protected ?array $nrSaBandsEutra;

    protected CapabilitySet $capabilitySet;

    protected MimoParser $mimoParser;
    protected ModulationParser $modulationParser;

    /**
     * **NOTE:** This parser requires an input containing `nrBands`, `nrNsaBandsEutra`, and/or `nrSaBandsEutra` data
     * rather than just the content of one of those arrays.
     *
     * @param array         $data
     * @param CapabilitySet $capabilitySet
     */
    public function __construct(array $data, CapabilitySet $capabilitySet)
    {
        $this->nrBands = Arr::get($data, 'nrBands');
        $this->nrNsaBandsEutra = Arr::get($data, 'nrNsaBandsEutra');
        $this->nrSaBandsEutra = Arr::get($data, 'nrSaBandsEutra');

        $this->capabilitySet = $capabilitySet;

        $this->mimoParser = new MimoParser();
        $this->modulationParser = new ModulationParser();
    }

    public function parseAndInsertAllModels(): void
    {
        $allBands = [];

        if ($this->nrBands) {
            $allBands = array_merge($allBands, array_map(fn ($b) => $b['band'], $this->nrBands));
        }

        if ($this->nrNsaBandsEutra) {
            $allBands = array_merge($allBands, array_map(fn ($b) => $b['band'], $this->nrNsaBandsEutra));
        }

        if ($this->nrSaBandsEutra) {
            $allBands = array_merge($allBands, array_map(fn ($b) => $b['band'], $this->nrSaBandsEutra));
        }

        $allBands = array_unique($allBands);

        $collection = new Collection();

        foreach ($allBands as $band) {
            $collection->push($this->parseSupportedNrBand($band));
        }

        $this->capabilitySet->supportedNrBands()->saveMany($collection);
    }

    protected function getValueFromArraysInOrder(string $key, ?array ...$arrays): mixed
    {
        foreach ($arrays as $array) {
            if ($array !== null && Arr::has($array, $key)) {
                return Arr::get($array, $key);
            }
        }

        return null;
    }

    protected function parseSupportedNrBand(int $band): SupportedNrBand
    {
        $nrBand = Arr::first($this->nrBands, fn ($b) => $b['band'] === $band);
        $nrNsaBandEutra = Arr::first($this->nrNsaBandsEutra, fn ($b) => $b['band'] === $band);
        $nrSaBandEutra = Arr::first($this->nrSaBandsEutra, fn ($b) => $b['band'] === $band);

        $eutraDataAvailable = $nrNsaBandEutra !== null || $nrSaBandEutra !== null;

        /** @var SupportedNrBand */
        $model = SupportedNrBand::firstOrCreate([
            'band'                    => $band,
            'max_uplink_duty_cycle'   => $this->getValueFromArraysInOrder('maxUplinkDutyCycle', $nrBand, $nrSaBandEutra, $nrNsaBandEutra),
            'power_class'             => $this->getValueFromArraysInOrder('powerClass', $nrBand, $nrSaBandEutra, $nrNsaBandEutra),
            'rate_matching_lte_crs'   => $this->getValueFromArraysInOrder('rateMatchingLteCrs', $nrBand, $nrSaBandEutra, $nrNsaBandEutra),
            'bandwidths'              => $this->getValueFromArraysInOrder('bandwidths', $nrBand, $nrSaBandEutra, $nrNsaBandEutra),
            'supports_endc'           => !$eutraDataAvailable ? null : $nrNsaBandEutra !== null,
            'supports_sa'             => !$eutraDataAvailable ? null : $nrSaBandEutra !== null,
            'capability_set_id'       => $this->capabilitySet->id,
        ]);

        $model->mimos()->saveMany($this->getMimoModels($nrBand, $nrSaBandEutra, $nrNsaBandEutra));
        $model->modulations()->saveMany($this->getModulationModels($nrBand, $nrSaBandEutra, $nrNsaBandEutra));

        return $model;
    }

    protected function getMimoModels(?array $nrBand, ?array $nrSaBandEutra, ?array $nrNsaBandEutra): Collection
    {
        $mimoDl = $this->mimoParser->getModelsFromData(
            ['mimoDl' => $this->getValueFromArraysInOrder('mimoDl', $nrBand, $nrSaBandEutra, $nrNsaBandEutra)],
            'mimoDl',
            false
        );
        $mimoUl = $this->mimoParser->getModelsFromData(
            ['mimoUl' => $this->getValueFromArraysInOrder('mimoUl', $nrBand, $nrSaBandEutra, $nrNsaBandEutra)],
            'mimoUl',
            true
        );

        return $mimoDl->merge($mimoUl);
    }

    protected function getModulationModels(?array $nrBand, ?array $nrSaBandEutra, ?array $nrNsaBandEutra): Collection
    {
        $modDl = $this->modulationParser->getModelsFromData(
            ['modulationDl' => $this->getValueFromArraysInOrder('modulationDl', $nrBand, $nrSaBandEutra, $nrNsaBandEutra)],
            'modulationDl',
            false
        );
        $modUl = $this->modulationParser->getModelsFromData(
            ['modulationUl' => $this->getValueFromArraysInOrder('modulationUl', $nrBand, $nrSaBandEutra, $nrNsaBandEutra)],
            'modulationUl',
            true
        );

        return $modDl->merge($modUl);
    }
}
