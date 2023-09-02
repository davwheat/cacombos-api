<?php

namespace App\DataParser;

use App\DataParser\ElementParser\BcsParser;
use App\DataParser\ElementParser\ComponentNrParser;
use App\DataParser\ElementParser\MimoParser;
use App\DataParser\ElementParser\ModulationParser;
use App\DataParser\Generators\ComboStringGenerator;
use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\NrComponent;
use BeyondCode\ServerTiming\Facades\ServerTiming;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NrDcParser implements DataParser
{
    protected array $data;
    protected CapabilitySet $capabilitySet;

    protected MimoParser $mimoParser;
    protected ModulationParser $modulationParser;
    protected BcsParser $bcsParser;
    protected ComponentNrParser $componentNrParser;

    protected ComboStringGenerator $comboStringGenerator;

    public function __construct(array $endcData, CapabilitySet $capabilitySet)
    {
        $this->data = $endcData;
        $this->capabilitySet = $capabilitySet;

        $this->mimoParser = new MimoParser();
        $this->modulationParser = new ModulationParser();
        $this->bcsParser = new BcsParser();
        $this->componentNrParser = new ComponentNrParser();

        $this->comboStringGenerator = new ComboStringGenerator();
    }

    public function parseAndInsertAllModels(): void
    {
        $modelAttributes = [];
        $nrComponentIds = [];

        foreach ($this->data as $i => $jsonData) {
            $data = $this->parseNrDcCombo($jsonData);

            $modelAttributes[$i] = $data[0];
            $nrComponentIds[$i] = $data[1];
        }

        // Insert
        Combo::insert($modelAttributes);
        $comboIds = Combo::where('capability_set_id', $this->capabilitySet->id)->pluck('id')->toArray();

        $i = -1;

        // Insert component IDs
        DB::table("combo_components")->insert(array_merge(...array_map(function ($id) use (&$i, $nrComponentIds) {
            $i++;

            /** @var array */
            $nr = $nrComponentIds[$i];

            $values = [];

            foreach ($nr as $nrId) {
                $values[] = [
                    'combo_id'         => $id,
                    'lte_component_id' => null,
                    'nr_component_id'  => $nrId,
                ];
            }

            return $values;
        }, $comboIds)));
    }

    protected function parseNrDcCombo(array $comboData): array
    {
        $bcsNr = $this->bcsParser->getBcsFromData($comboData, 'bcs');
        $nrComponents = $this->getComponentNrModels($comboData);

        $attributes = [
            'combo_string'                         => $this->nrcaToComboString($nrComponents->all()),
            'capability_set_id'                    => $this->capabilitySet->id,
            'bandwidth_combination_set_nr'         => json_encode($bcsNr),
        ];

        return [$attributes, $nrComponents->pluck('id')->toArray()];
    }

    /**
     * @return Collection<NrComponent>
     */
    protected function getComponentNrModels(array $combo)
    {
        return $this->componentNrParser->getModelsFromData([
            'componentsNr' => array_merge($combo['componentsFr1'], $combo['componentsFr2']),
        ], 'componentsNr');
    }

    protected function nrcaToComboString(array $components): string
    {
        return $this->comboStringGenerator->getComboStringFromComponents($components);
    }
}
