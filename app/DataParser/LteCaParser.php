<?php

namespace App\DataParser;

use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\LteComponent;
use App\Models\Mimo;
use Illuminate\Database\Eloquent\Collection;

class LteCaParser
{
    protected array $data;
    protected CapabilitySet $capabilitySet;

    protected $mimoCache = ['ul' => [], 'dl' => []];

    public function __construct(array $lteCaData, CapabilitySet $capabilitySet)
    {
        $this->data = $lteCaData;
        $this->capabilitySet = $capabilitySet;
    }

    public function parseAndInsertModels(): void
    {
        $collection = new Collection();

        foreach ($this->data as $lteCa) {
            $collection->push($this->parseLteCa($lteCa));
        }
    }

    protected function parseLteCa(array $combo): Collection
    {
        $collection = new Collection();

        $combo = Combo::firstOrCreate([
            'combo_string'              => $this->lteCaToComboString($combo),
            'capability_set_id'         => $this->capabilitySet->id,
            'bandwidth_combination_set' => $this->getBcs($combo),
        ]);

        $models = $this->getComponentModels($combo['components']);

        return $collection;
    }

    protected function getBcs(array $combo): ?array
    {
        if (empty($combo['bcs'])) {
            return [];
        }

        switch ($combo['bcs']['type']) {
            case 'all':
                return ['all'];

            case 'multi':
                return $combo['bcs']['value'];

            case 'single':
                return [$combo['bcs']['value']];

            default:
            case 'empty':
                return null;
        }
    }

    protected function getMimoFromComponent(array $component, bool $isUl): ?array
    {
        $key = $isUl ? 'mimoUl' : 'mimoDl';

        if (empty($component[$key])) {
            return null;
        }

        $mimoData = $component[$key];

        switch ($mimoData['type']) {
            case 'single':
                return [$mimoData['value']];

            case 'mixed':
                return $mimoData['value'];

            case 'empty':
            default:
                return null;
        }
    }

    protected function getComponentModels(array $combo): Collection
    {
        $models = new Collection();

        foreach ($combo['components'] as $i => $component) {
            /**
             * @var int   $i
             * @var array $component
             */
            $model = new LteComponent();

            $model->band = $component['band'];
            $model->dl_class = $component['bwClassDl'];
            $model->ul_class = $component['bwClassUl'];

            $dlMimo = $this->getMimoFromComponent($component, false);
            $ulMimo = $this->getMimoFromComponent($component, true);

            $mimoModels = new Collection();

            // Find and attach MIMO models

            foreach ($dlMimo as $m) {
                if (empty($this->mimoCache['dl'][$m])) {
                    $this->mimoCache['dl'][$m] = Mimo::firstOrCreate([
                        'mimo'  => $m,
                        'is_ul' => false,
                    ]);

                    $mimoModels->push($this->mimoCache['dl'][$m]);
                }
            }

            foreach ($ulMimo as $m) {
                if (empty($this->mimoCache['ul'][$m])) {
                    $this->mimoCache['ul'][$m] = Mimo::firstOrCreate([
                        'mimo'  => $m,
                        'is_ul' => true,
                    ]);

                    $mimoModels->push($this->mimoCache['ul'][$m]);
                }
            }

            $model->saveOrFail();
            $model->mimos()->attach($mimoModels->pluck('id'));

            $models->push($model);
        }

        return $models;
    }

    protected function lteCaToComboString(array $combo): string
    {
        $comboStringComponents = [];

        foreach ($combo['components'] as $lteCa) {
            $component = $lteCa['band'];

            if (isset($lteCa['bwClassDl'])) {
                $component .= $lteCa['bwClassDl'];
            }

            if (isset($lteCa['mimoDl'])) {
                switch ($lteCa['mimoDl']['type']) {
                    case 'single':
                        $component .= $lteCa['mimoDl']['type']['value'];
                        break;

                    case 'mixed':
                        /** @var array */
                        $allValues = $lteCa['mimoDl']['type']['value'];
                        $component .= max($allValues);
                        break;

                    default:
                        break;
                }
            }

            if (isset($lteCa['bwClassUl'])) {
                $component .= $lteCa['bwClassUl'];
            }

            $comboStringComponents[] = $component;
        }

        $comboString = implode('-', $comboStringComponents);

        return $comboString;
    }
}
