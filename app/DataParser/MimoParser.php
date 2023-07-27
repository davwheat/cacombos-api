<?php

namespace App\DataParser;

use App\Models\Mimo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class MimoParser
{
    private $mimoCache = ['ul' => [], 'dl' => []];

    /**
     * @return Collection<Mimo>
     */
    public function getModelsFromData(array $data, string $attribute, bool $isUl): Collection
    {
        $mimoData = Arr::get($data, $attribute);
        $value = $this->getMimoIntArray($mimoData);

        return $this->getModel($value, $isUl);
    }

    private function getMimoIntArray(?array $data): ?array
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
     * @return Collection<Mimo>
     */
    private function getModel(?array $value, bool $isUl): Collection
    {
        $cache = &$this->mimoCache[$isUl ? 'ul' : 'dl'];

        $mimoModels = new Collection();

        if (!empty($value)) {
            foreach ($value as $m) {
                if (empty($cache[$m])) {
                    $cache[$m] = Mimo::firstOrCreate([
                        'mimo'  => $m,
                        'is_ul' => $isUl,
                    ]);
                }

                $mimoModels->push($cache[$m]);
            }
        }

        return $mimoModels;
    }
}
