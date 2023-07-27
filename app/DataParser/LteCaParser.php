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
        $collection = new Collection();

        foreach ($this->data as $lteCa) {
            $collection->push($this->parseLteCaCombo($lteCa));
        }
    }

    protected function parseLteCaCombo(array $comboData): Combo
    {
        $lteComponents = $this->getComponentLteModels($comboData);

        /** @var Combo */
        $comboModel = Combo::firstOrCreate([
            'combo_string'                    => $this->lteCaToComboString($lteComponents->all()),
            'capability_set_id'               => $this->capabilitySet->id,
            'bandwidth_combination_set_eutra' => $this->getBcs($comboData),
        ]);

        $comboModel->lteComponents()->saveMany($lteComponents);

        return $comboModel;
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
