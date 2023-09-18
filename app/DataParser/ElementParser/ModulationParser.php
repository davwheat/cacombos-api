<?php

namespace App\DataParser\ElementParser;

use App\Models\Modulation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class ModulationParser
{
    private $modCache = ['ul' => [], 'dl' => []];

    /**
     * @return Collection<Modulation>
     */
    public function getModelsFromData(array $data, string $attribute, bool $isUl): Collection
    {
        $modData = Arr::get($data, $attribute);
        $value = $this->getModStringArray($modData);

        return $this->getModel($value, $isUl);
    }

    private function getModStringArray(?array $data): ?array
    {
        if (empty($data)) {
            return null;
        }

        switch ($data['type']) {
            case 'single':
                return [$data['value']];

            case 'mixed':
                return $data['value'];

            case 'empty':
            default:
                return null;
        }
    }

    /**
     * @return Collection<Modulation>
     */
    private function getModel(?array $value, bool $isUl): Collection
    {
        $cache = &$this->modCache[$isUl ? 'ul' : 'dl'];

        $modulationModels = new Collection();

        if (!empty($value)) {
            foreach ($value as $m) {
                if (empty($cache[$m])) {
                    $cache[$m] = Modulation::firstOrCreate([
                        'modulation'  => $m,
                        'is_ul'       => $isUl,
                    ]);
                }

                $modulationModels->push($cache[$m]);
            }
        }

        return $modulationModels;
    }
}
