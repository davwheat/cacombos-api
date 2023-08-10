<?php

namespace App\DataParser\ElementParser;

use App\Models\Mimo;
use App\Models\Modulation;
use App\Models\NrComponent;
use Illuminate\Database\Eloquent\Collection;

class ComponentNrParser
{
    protected MimoParser $mimoParser;
    protected ModulationParser $modulationParser;

    public function __construct()
    {
        $this->mimoParser = new MimoParser();
        $this->modulationParser = new ModulationParser();
    }

    /**
     * @return Collection<NrComponent>
     */
    public function getModelsFromData(array $data, string $attribute): Collection
    {
        return $this->getNrComponentModels($data[$attribute]);
    }

    /**
     * @return Collection<NrComponent>
     */
    private function getNrComponentModels(array $components): Collection
    {
        $models = new Collection();

        foreach ($components as $i => $component) {
            /**
             * @var int   $i
             * @var array $component
             */
            $model = new NrComponent();

            $model->band = $component['band'];

            if (empty($component['bwClassDl'])) {
                $model->dl_class = null;
            } else {
                $model->dl_class = $component['bwClassDl'];
            }

            if (empty($component['bwClassUl'])) {
                $model->ul_class = null;
            } else {
                $model->ul_class = $component['bwClassUl'];
            }

            $model->component_index = $i;

            $allMimos = collect()
                ->concat($this->getMimosFromComponent($component, false))
                ->concat($this->getMimosFromComponent($component, true));

            $allModulations = collect()
                ->concat($this->getModulationsFromComponent($component, false))
                ->concat($this->getModulationsFromComponent($component, true));

            if (empty($component['maxBw'])) {
                $model->bandwidth = null;
            } else {
                $model->bandwidth = $component['maxBw'];
            }

            if (empty($component['bw90mhzSupported'])) {
                $model->supports_90mhz_bw = null;
            } else {
                $model->supports_90mhz_bw = $component['bw90mhzSupported'];
            }

            if (empty($component['maxScs'])) {
                $model->subcarrier_spacing = null;
            } else {
                $model->subcarrier_spacing = $component['maxScs'];
            }

            $model->saveOrFail();

            // Attach MIMOs and modulations to saved component
            $model->mimos()->sync($allMimos->pluck('id'));
            $model->modulations()->sync($allModulations->pluck('id'));

            $models->push($model);
        }

        return $models;
    }

    /**
     * @return Collection<Mimo>
     */
    private function getMimosFromComponent(array $component, bool $isUl): Collection
    {
        return $this->mimoParser->getModelsFromData($component, $isUl ? 'mimoUl' : 'mimoDl', $isUl);
    }

    /**
     * @return Collection<Modulation>
     */
    private function getModulationsFromComponent(array $component, bool $isUl): Collection
    {
        return $this->modulationParser->getModelsFromData($component, $isUl ? 'modulationUl' : 'modulationDl', $isUl);
    }
}
