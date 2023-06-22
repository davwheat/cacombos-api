<?php

namespace App\DataParser;

use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\LteComponent;
use Illuminate\Database\Eloquent\Collection;

class LteCaParser
{
    private array $data;
    private CapabilitySet $capabilitySet;

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
            'combo_string' => $this->lteCaToComboString($combo),
            'capability_set_id' => $this->capabilitySet->id,
            'bandwidth_combination_set' => $this->getBcs($combo),
        ]);

        foreach ($combo['components'] as $lteCa) {
            $collection->push($this->parseLteCaComponent($lteCa));
        }

        return $collection;
    }

    protected function getBcs(array $combo): ?array
    {
        if (empty($combo['bcs'])) return [];

        switch ($combo['bcs']['type']) {
            case 'all':
                return ["all"];

            case 'multi':
                return $combo['bcs']['value'];

            case 'single':
                return [$combo['bcs']['value']];

            default:
            case 'empty':
                return null;
        }
    }

    protected function parseLteCaComponent(array $combo): ?LteComponent
    {
        // TODO

        return null;
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
