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
use App\Models\Mimo;
use App\Models\Modulation;
use App\Models\NrComponent;
use Illuminate\Database\Eloquent\Collection;

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
        $collection = new Collection();

        foreach ($this->data as $lteCa) {
            $collection->push($this->parseEndcCombo($lteCa));
        }
    }

    protected function parseEndcCombo(array $comboData): Combo
    {
        $bcsNr = $this->bcsParser->getBcsFromData($comboData, 'bcsNr');
        $bcsEutra = $this->bcsParser->getBcsFromData($comboData, 'bcsEutra');
        $bcsIntraEndc = $this->bcsParser->getBcsFromData($comboData, 'bcsIntraEndc');

        $lteComponents = $this->getComponentLteModels($comboData);
        $nrComponents = $this->getComponentNrModels($comboData);

        $allComponents = collect()->concat($lteComponents)->concat($nrComponents);

        /** @var Combo */
        $comboModel = Combo::firstOrCreate([
            'combo_string'                         => $this->enDcToComboString($allComponents->all()),
            'capability_set_id'                    => $this->capabilitySet->id,
            'bandwidth_combination_set_eutra'      => $bcsEutra,
            'bandwidth_combination_set_nr'         => $bcsNr,
            'bandwidth_combination_set_intra_endc' => $bcsIntraEndc,
        ]);

        $comboModel->lteComponents()->saveMany($lteComponents);
        $comboModel->nrComponents()->saveMany($nrComponents);

        return $comboModel;
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
