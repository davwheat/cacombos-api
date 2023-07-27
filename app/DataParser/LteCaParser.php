<?php

namespace App\DataParser;

use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\LteComponent;
use App\Models\Mimo;
use App\Models\Modulation;
use Illuminate\Database\Eloquent\Collection;

class LteCaParser implements DataParser
{
    protected array $data;
    protected CapabilitySet $capabilitySet;

    protected $mimoCache = ['ul' => [], 'dl' => []];
    protected $modulationCache = ['ul' => [], 'dl' => []];

    public function __construct(array $lteCaData, CapabilitySet $capabilitySet)
    {
        $this->data = $lteCaData;
        $this->capabilitySet = $capabilitySet;
    }

    public function parseAndInsertAllModels(): void
    {
        $collection = new Collection();

        foreach ($this->data as $lteCa) {
            $collection->push($this->parseLteCaCombo($lteCa));
        }
    }

    protected function parseLteCaCombo(array $comboData): Combo
    {
        /** @var Combo */
        $comboModel = Combo::firstOrCreate([
            'combo_string'              => $this->lteCaToComboString($comboData),
            'capability_set_id'         => $this->capabilitySet->id,
            'bandwidth_combination_set' => $this->getBcs($comboData),
        ]);

        $lteComponents = $this->getComponentModels($comboData, $comboModel);

        $comboModel->lteComponents()->saveMany($lteComponents);

        return $comboModel;
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

    protected function getModulationFromComponent(array $component, bool $isUl): ?array
    {
        $key = $isUl ? 'modulationUl' : 'modulationDl';

        if (empty($component[$key])) {
            return null;
        }

        $modData = $component[$key];

        switch ($modData['type']) {
            case 'single':
                return [$modData['value']];

            case 'mixed':
                return $modData['value'];

            case 'empty':
            default:
                return null;
        }
    }

    protected function getComponentModels(array $combo, Combo $comboModel): Collection
    {
        $models = new Collection();

        foreach ($combo['components'] as $i => $component) {
            /**
             * @var int   $i
             * @var array $component
             */
            $model = new LteComponent();

            $model->band = $component['band'];

            if (empty($component['bwClassDl'])) {
                $model->dl_class = null;
            } else {
                $model->dl_class = $component['bwClassDl'];
            }

            if (empty($component['bwClassUl'])) {
                $model->ul_class = null;
            } else {
                $model->ul_class = $component['bwClassUl'];
            }

            $model->component_index = $i;

            $dlMimo = $this->getMimoFromComponent($component, false);
            $ulMimo = $this->getMimoFromComponent($component, true);

            $mimoModels = new Collection();

            // Find and attach MIMO models

            if (!empty($dlMimo)) {
                foreach ($dlMimo as $m) {
                    if (empty($this->mimoCache['dl'][$m])) {
                        $this->mimoCache['dl'][$m] = Mimo::firstOrCreate([
                            'mimo'  => $m,
                            'is_ul' => false,
                        ]);
                    }

                    $mimoModels->push($this->mimoCache['dl'][$m]);
                }
            }

            if (!empty($ulMimo)) {
                foreach ($ulMimo as $m) {
                    if (empty($this->mimoCache['ul'][$m])) {
                        $this->mimoCache['ul'][$m] = Mimo::firstOrCreate([
                            'mimo'  => $m,
                            'is_ul' => true,
                        ]);
                    }

                    $mimoModels->push($this->mimoCache['ul'][$m]);
                }
            }

            $dlMod = $this->getModulationFromComponent($component, false);
            $ulMod = $this->getModulationFromComponent($component, true);

            $modModels = new Collection();

            // Find and attach Modulation models

            if (!empty($dlMod)) {
                foreach ($dlMod as $m) {
                    if (empty($this->modulationCache['dl'][$m])) {
                        $this->modulationCache['dl'][$m] = Modulation::firstOrCreate([
                            'modulation' => $m,
                            'is_ul'      => false,
                        ]);
                    }

                    $modModels->push($this->modulationCache['dl'][$m]);
                }
            }

            if (!empty($ulMod)) {
                foreach ($ulMod as $m) {
                    if (empty($this->modulationCache['ul'][$m])) {
                        $this->modulationCache['ul'][$m] = Modulation::firstOrCreate([
                            'modulation' => $m,
                            'is_ul'      => true,
                        ]);
                    }

                    $modModels->push($this->modulationCache['ul'][$m]);
                }
            }

            $model->saveOrFail();

            $model->mimos()->sync($mimoModels->pluck('id'));
            $model->modulations()->sync($modModels->pluck('id'));

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
                        $component .= $lteCa['mimoDl']['value'];
                        break;

                    case 'mixed':
                        /** @var array */
                        $allValues = $lteCa['mimoDl']['value'];
                        $component .= max($allValues);
                        break;

                    default:
                        break;
                }
            }

            if (isset($lteCa['bwClassUl'])) {
                $component .= $lteCa['bwClassUl'];
            }

            if (isset($lteCa['mimoUl'])) {
                switch ($lteCa['mimoUl']['type']) {
                    case 'single':
                        $val = $lteCa['mimoUl']['value'];

                        if ($val !== 1) {
                            $component .= $val;
                        }
                        break;

                    case 'mixed':
                        /** @var array */
                        $allValues = $lteCa['mimoUl']['value'];
                        $val = max($allValues);

                        if ($val !== 1) {
                            $component .= max($allValues);
                        }
                        break;

                    default:
                        break;
                }
            }

            $comboStringComponents[] = $component;
        }

        $comboString = implode('-', $comboStringComponents);

        return $comboString;
    }
}
