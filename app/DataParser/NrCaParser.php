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
use Illuminate\Database\Eloquent\Collection;

class NrCaParser implements DataParser
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
        $collection = new Collection();

        foreach ($this->data as $lteCa) {
            $collection->push($this->parseNrcaCombo($lteCa));
        }
    }

    protected function parseNrcaCombo(array $comboData): Combo
    {
        $bcsNr = $this->bcsParser->getBcsFromData($comboData, 'bcs');

        $nrComponents = $this->getComponentNrModels($comboData);

        /** @var Combo */
        $comboModel = Combo::firstOrCreate([
            'combo_string'                         => $this->nrcaToComboString($nrComponents->all()),
            'capability_set_id'                    => $this->capabilitySet->id,
            'bandwidth_combination_set_nr'         => $bcsNr,
        ]);

        $comboModel->nrComponents()->saveMany($nrComponents);

        return $comboModel;
    }

    /**
     * @return Collection<NrComponent>
     */
    protected function getComponentNrModels(array $combo)
    {
        return $this->componentNrParser->getModelsFromData($combo, 'componentsNr');
    }

    protected function nrcaToComboString(array $components): string
    {
        return $this->comboStringGenerator->getComboStringFromComponents($components);
    }
}
