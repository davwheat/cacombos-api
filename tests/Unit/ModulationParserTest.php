<?php

namespace Tests\Unit\DataParser;

use App\DataParser\ElementParser\ModulationParser;
use App\Models\Modulation;
use Illuminate\Support\Arr;
use Tests\UnitTestCase;

class ModulationParserTest extends UnitTestCase
{
    private function getComponentData(?array $mod = null)
    {
        $data = [
            'band' => 1,
        ];

        if ($mod !== null) {
            $data['modulationDl'] = $mod;
        }

        return $data;
    }

    public function test_get_missing_modulation()
    {
        $p = new ModulationParser();

        $mod = $p->getModelsFromData($this->getComponentData(), 'modulationDl', false);

        $mod = $mod->map(
            static fn (Modulation $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([], $mod->toArray());
    }

    public function test_get_empty_modulation()
    {
        $p = new ModulationParser();

        $mod = $p->getModelsFromData($this->getComponentData(['type' => 'empty']), 'modulationDl', false);

        $mod = $mod->map(
            static fn (Modulation $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([], $mod->toArray());
    }

    public function test_get_single_modulation()
    {
        $p = new ModulationParser();

        $mod = $p->getModelsFromData($this->getComponentData([
            'type'  => 'single',
            'value' => 'qpsk',
        ]), 'modulationDl', false);

        $mod = $mod->map(
            static fn (Modulation $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([['modulation' => 'qpsk', 'is_ul' => false]], $mod->toArray());
    }

    public function test_get_single_modulation_ul()
    {
        $p = new ModulationParser();

        $mod = $p->getModelsFromData($this->getComponentData([
            'type'  => 'single',
            'value' => 'qpsk',
        ]), 'modulationDl', true);

        $mod = $mod->map(
            static fn (Modulation $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([['modulation' => 'qpsk', 'is_ul' => true]], $mod->toArray());
    }

    public function test_get_mixed_modulation()
    {
        $p = new ModulationParser();

        $mod = $p->getModelsFromData($this->getComponentData([
            'type'  => 'mixed',
            'value' => [
                'qpsk',
                '16qam',
                '64qam',
                '256qam',
            ],
        ]), 'modulationDl', false);

        $mod = $mod->map(
            static fn (Modulation $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([
            ['modulation' => 'qpsk', 'is_ul' => false],
            ['modulation' => '16qam', 'is_ul' => false],
            ['modulation' => '64qam', 'is_ul' => false],
            ['modulation' => '256qam', 'is_ul' => false],
        ], $mod->toArray());
    }
}
