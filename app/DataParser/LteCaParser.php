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

    protected MimoParser $mimoParser;
    protected ModulationParser $modulationParser;

    public function __construct(array $lteCaData, CapabilitySet $capabilitySet)
    {
        $this->data = $lteCaData;
        $this->capabilitySet = $capabilitySet;
        $this->mimoParser = new MimoParser();
        $this->modulationParser = new ModulationParser();
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

    /**
     * @return Collection<Mimo>
     */
    protected function getMimosFromComponent(array $component, bool $isUl): Collection
    {
        return $this->mimoParser->getModelsFromData($component, $isUl ? 'mimoUl' : 'mimoDl', $isUl);
    }

    /**
     * @return Collection<Modulation>
     */
    protected function getModulationsFromComponent(array $component, bool $isUl): Collection
    {
        return $this->modulationParser->getModelsFromData($component, $isUl ? 'modulationUl' : 'modulationDl', $isUl);
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

            $allMimos = collect()
                ->concat($this->getMimosFromComponent($component, false))
                ->concat($this->getMimosFromComponent($component, true));

            $allModulations = collect()
                ->concat($this->getModulationsFromComponent($component, false))
                ->concat($this->getModulationsFromComponent($component, true));

            $model->saveOrFail();

            // Attach MIMOs and modulations to saved component
            $model->mimos()->sync($allMimos->pluck('id'));
            $model->modulations()->sync($allModulations->pluck('id'));

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
