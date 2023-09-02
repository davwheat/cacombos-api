<?php

namespace App\DataParser;

use App\DataParser\ElementParser\BcsParser;
use App\DataParser\ElementParser\ComponentLteParser;
use App\DataParser\ElementParser\MimoParser;
use App\DataParser\ElementParser\ModulationParser;
use App\DataParser\Generators\ComboStringGenerator;
use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\LteComponent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LteCaParser implements DataParser
{
    protected array $data;
    protected CapabilitySet $capabilitySet;

    protected MimoParser $mimoParser;
    protected ModulationParser $modulationParser;
    protected BcsParser $bcsParser;
    protected ComponentLteParser $componentLteParser;

    protected ComboStringGenerator $comboStringGenerator;

    public function __construct(array $lteCaData, CapabilitySet $capabilitySet)
    {
        $this->data = $lteCaData;
        $this->capabilitySet = $capabilitySet;

        $this->mimoParser = new MimoParser();
        $this->modulationParser = new ModulationParser();
        $this->bcsParser = new BcsParser();
        $this->componentLteParser = new ComponentLteParser();

        $this->comboStringGenerator = new ComboStringGenerator();
    }

    public function parseAndInsertAllModels(): void
    {
        $modelAttributes = [];
        $lteComponentIds = [];

        foreach ($this->data as $i => $jsonData) {
            $data = $this->parseLteCaCombo($jsonData);

            $modelAttributes[$i] = $data[0];
            $lteComponentIds[$i] = $data[1];
        }

        // Insert
        Combo::insert($modelAttributes);
        $comboIds = Combo::where('capability_set_id', $this->capabilitySet->id)->pluck('id')->toArray();

        $i = -1;

        // Insert component IDs
        DB::table('combo_components')->insert(array_merge(...array_map(function ($id) use (&$i, $lteComponentIds) {
            $i++;

            /** @var array */
            $lte = $lteComponentIds[$i];

            $values = [];

            foreach ($lte as $lteId) {
                $values[] = [
                    'combo_id'         => $id,
                    'lte_component_id' => $lteId,
                    'nr_component_id'  => null,
                ];
            }

            return $values;
        }, $comboIds)));
    }

    protected function parseLteCaCombo(array $comboData): array
    {
        $lteComponents = $this->getComponentLteModels($comboData);

        // Manually generate combo attributes for mass insertion later
        $attributes = [
            'combo_string'                         => $this->lteCaToComboString($lteComponents->all()),
            'capability_set_id'                    => $this->capabilitySet->id,
            'bandwidth_combination_set_eutra'      => json_encode($this->getBcs($comboData)),
        ];

        $d = [$attributes, $lteComponents->pluck('id')->toArray()];

        return $d;
    }

    protected function getBcs(array $combo): ?array
    {
        return $this->bcsParser->getBcsFromData($combo, 'bcs');
    }

    /**
     * @return Collection<LteComponent>
     */
    protected function getComponentLteModels(array $combo): Collection
    {
        return $this->componentLteParser->getModelsFromData($combo, 'components');
    }

    protected function lteCaToComboString(array $components): string
    {
        return $this->comboStringGenerator->getComboStringFromComponents($components);
    }
}
