<?php

namespace App\DataParser;

use App\Models\Combo;
use Illuminate\Database\Eloquent\Collection;

class LteCaParser
{
    private array $data;

    public function __construct(array $lteCaData)
    {
        $this->data = $lteCaData;
    }

    public function parseToModels(): Collection
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
            'band' => $combo['band'],
        ]);

        foreach ($combo['components'] as $lteCa) {
            $collection->push($this->parseLteCaComponent($lteCa));
        }

        return $collection;
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
