<?php

namespace App\DataParser;

use App\DataParser\ElementParser\BcsParser;
use App\DataParser\ElementParser\ComponentLteParser;
use App\DataParser\ElementParser\MimoParser;
use App\DataParser\ElementParser\ModulationParser;
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
    protected BcsParser $bcsParser;
    protected ComponentLteParser $componentLteParser;

    public function __construct(array $lteCaData, CapabilitySet $capabilitySet)
    {
        $this->data = $lteCaData;
        $this->capabilitySet = $capabilitySet;
        $this->mimoParser = new MimoParser();
        $this->modulationParser = new ModulationParser();
        $this->bcsParser = new BcsParser();
        $this->componentLteParser = new ComponentLteParser();
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
            'combo_string'                    => $this->lteCaToComboString($comboData),
            'capability_set_id'               => $this->capabilitySet->id,
            'bandwidth_combination_set_eutra' => $this->getBcs($comboData),
        ]);

        $lteComponents = $this->getComponentLteModels($comboData);

        $comboModel->lteComponents()->saveMany($lteComponents);

        return $comboModel;
    }

    protected function getBcs(array $combo): ?array
    {
        return $this->bcsParser->getBcsFromData($combo, 'bcs');
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

    /**
     * @return Collection<LteComponent>
     */
    protected function getComponentLteModels(array $combo): Collection
    {
        return $this->componentLteParser->getModelsFromData($combo, 'components');
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
