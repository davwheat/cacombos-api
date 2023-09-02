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
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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
        $modelAttributes = [];
        $nrComponentIds = [];

        foreach ($this->data as $i => $jsonData) {
            $data = $this->parseNrcaCombo($jsonData);

            $modelAttributes[$i] = $data[0];
            $nrComponentIds[$i] = $data[1];
        }

        // Insert
        Combo::insert($modelAttributes);
        $comboIds = Combo::query()->where('capability_set_id', $this->capabilitySet->id)
            ->whereDoesntHave('nrComponents')
            ->whereDoesntHave('lteComponents')
            ->pluck('id')
            ->toArray();

        if (count($comboIds) !== count($nrComponentIds)) {
            $cCombo = count($comboIds);
            $cNr = count($nrComponentIds);

            if ($cCombo > $cNr) {
                // Dump extras
                var_dump(array_slice($comboIds, $cNr));
            }

            throw new UnprocessableEntityHttpException("Combo IDs ($cCombo) and component IDs ($cNr) length do not match");
        }

        $i = -1;

        // Insert component IDs
        DB::table('combo_components')->insert(array_merge(...array_map(function ($id) use (&$i, $nrComponentIds) {
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

    protected function parseNrcaCombo(array $comboData): array
    {
        $bcsNr = $this->bcsParser->getBcsFromData($comboData, 'bcs');

        $nrComponents = $this->getComponentNrModels($comboData);

        // Manually generate combo attributes for mass insertion later
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
        return $this->componentNrParser->getModelsFromData($combo, 'components');
    }

    protected function nrcaToComboString(array $components): string
    {
        return $this->comboStringGenerator->getComboStringFromComponents($components);
    }
}
