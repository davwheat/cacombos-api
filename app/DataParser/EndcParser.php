<?php

namespace App\DataParser;

use App\DataParser\ElementParser\BcsParser;
use App\DataParser\ElementParser\ComponentLteParser;
use App\DataParser\ElementParser\ComponentNrParser;
use App\DataParser\ElementParser\MimoParser;
use App\DataParser\ElementParser\ModulationParser;
use App\DataParser\Generators\ComboStringGenerator;
use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\LteComponent;
use App\Models\NrComponent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EndcParser implements DataParser
{
    protected array $data;
    protected CapabilitySet $capabilitySet;

    protected MimoParser $mimoParser;
    protected ModulationParser $modulationParser;
    protected BcsParser $bcsParser;
    protected ComponentLteParser $componentLteParser;
    protected ComponentNrParser $componentNrParser;

    protected ComboStringGenerator $comboStringGenerator;

    public function __construct(array $endcData, CapabilitySet $capabilitySet)
    {
        $this->data = $endcData;
        $this->capabilitySet = $capabilitySet;

        $this->mimoParser = new MimoParser();
        $this->modulationParser = new ModulationParser();
        $this->bcsParser = new BcsParser();
        $this->componentLteParser = new ComponentLteParser();
        $this->componentNrParser = new ComponentNrParser();

        $this->comboStringGenerator = new ComboStringGenerator();
    }

    public function parseAndInsertAllModels(): void
    {
        $modelAttributes = [];
        $lteComponentIds = [];
        $nrComponentIds = [];

        foreach ($this->data as $i => $jsonData) {
            $data = $this->parseEndcCombo($jsonData);

            $modelAttributes[$i] = $data[0];
            $lteComponentIds[$i] = $data[1];
            $nrComponentIds[$i] = $data[2];
        }

        // Insert
        Combo::insert($modelAttributes);
        $comboIds = Combo::where('capability_set_id', $this->capabilitySet->id)->pluck('id')->toArray();

        $i = -1;

        // Insert component IDs
        DB::table('combo_components')->insert(array_merge(...array_map(function ($id) use (&$i, $lteComponentIds, $nrComponentIds) {
            $i++;

            /** @var array */
            $lte = $lteComponentIds[$i];
            /** @var array */
            $nr = $nrComponentIds[$i];

            $values = [];

            foreach ($lte as $lteId) {
                $values[] = [
                    'combo_id'         => $id,
                    'lte_component_id' => $lteId,
                    'nr_component_id'  => null,
                ];
            }

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

    protected function parseEndcCombo(array $comboData): array
    {
        $bcsNr = $this->bcsParser->getBcsFromData($comboData, 'bcsNr');
        $bcsEutra = $this->bcsParser->getBcsFromData($comboData, 'bcsEutra');
        $bcsIntraEndc = $this->bcsParser->getBcsFromData($comboData, 'bcsIntraEndc');

        $lteComponents = $this->getComponentLteModels($comboData);
        $nrComponents = $this->getComponentNrModels($comboData);

        $allComponents = collect()->concat($lteComponents)->concat($nrComponents);

        // Manually generate combo attributes for mass insertion later
        $attributes = [
            'combo_string'                         => $this->enDcToComboString($allComponents->all()),
            'capability_set_id'                    => $this->capabilitySet->id,
            'bandwidth_combination_set_eutra'      => json_encode($bcsEutra),
            'bandwidth_combination_set_nr'         => json_encode($bcsNr),
            'bandwidth_combination_set_intra_endc' => json_encode($bcsIntraEndc),
        ];

        return [$attributes, $lteComponents->pluck('id')->toArray(), $nrComponents->pluck('id')->toArray()];
    }

    /**
     * @return Collection<LteComponent>
     */
    protected function getComponentLteModels(array $combo)
    {
        return $this->componentLteParser->getModelsFromData($combo, 'componentsLte');
    }

    /**
     * @return Collection<NrComponent>
     */
    protected function getComponentNrModels(array $combo)
    {
        return $this->componentNrParser->getModelsFromData($combo, 'componentsNr');
    }

    protected function enDcToComboString(array $components): string
    {
        return $this->comboStringGenerator->getComboStringFromComponents($components);
    }
}
