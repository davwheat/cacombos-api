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
        $collection = new Collection();

        foreach ($this->data as $i => $lteCa) {
            clock()->event("Parsing combo $i")->begin();
            $collection->push($this->parseNrcaCombo($lteCa));
            clock()->event("Parsing combo $i")->end();
        }
    }

    protected function parseNrcaCombo(array $comboData): Combo
    {
        clock()->event('Extracting BCS')->begin();
        $bcsNr = $this->bcsParser->getBcsFromData($comboData, 'bcs');
        clock()->event('Extracting BCS')->end();

        clock()->event('Extracting FR1 and FR2 components')->begin();
        $nrComponents = $this->getComponentNrModels($comboData);
        clock()->event('Extracting FR1 and FR2 components')->end();

        clock()->event('Saving models')->begin();
        /** @var Combo */
        $comboModel = Combo::firstOrCreate([
            'combo_string'                         => $this->nrcaToComboString($nrComponents->all()),
            'capability_set_id'                    => $this->capabilitySet->id,
            'bandwidth_combination_set_nr'         => $bcsNr,
        ]);

        $comboModel->nrComponents()->saveMany($nrComponents);
        clock()->event('Saving models')->end();

        return $comboModel;
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
