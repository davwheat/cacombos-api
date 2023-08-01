<?php

namespace Tests\Unit\DataParser;

use App\DataParser\ElementParser\MimoParser;
use App\Models\Mimo;
use Illuminate\Support\Arr;
use Tests\UnitTestCase;

class MimoParserTest extends UnitTestCase
{
    private function getComponentData(?array $mimo = null)
    {
        $data = [
            'band' => 1,
        ];

        if ($mimo !== null) {
            $data['mimoDl'] = $mimo;
        }

        return $data;
    }

    public function test_get_missing_mimo()
    {
        $p = new MimoParser();

        $mimo = $p->getModelsFromData($this->getComponentData(), 'mimoDl', false);

        $mimo = $mimo->map(
            static fn (Mimo $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([], $mimo->toArray());
    }

    /**
     * Gets correct value for "empty" MIMO
     */
    public function test_get_empty_mimo()
    {
        $p = new MimoParser();

        $mimo = $p->getModelsFromData($this->getComponentData([
            'type' => "empty"
        ]), 'mimoDl', false);

        $mimo = $mimo->map(
            static fn (Mimo $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([], $mimo->toArray());
    }

    /**
     * Gets correct value for "single" MIMO
     */
    public function test_get_single_mimo()
    {
        $p = new MimoParser();

        $mimo = $p->getModelsFromData($this->getComponentData([
            'type' => "single",
            'value' => 1
        ]), 'mimoDl', false);

        $mimo = $mimo->map(
            static fn (Mimo $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([[
            'mimo' => 1,
            'is_ul' => false,
        ]], $mimo->toArray());
    }

    /**
     * Gets correct value for "single" MIMO
     */
    public function test_get_single_mimo_ul()
    {
        $p = new MimoParser();

        $mimo = $p->getModelsFromData($this->getComponentData([
            'type' => "single",
            'value' => 1
        ]), 'mimoDl', true);

        $mimo = $mimo->map(
            static fn (Mimo $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([[
            'mimo' => 1,
            'is_ul' => true,
        ]], $mimo->toArray());
    }

    /**
     * Gets correct value for "mixed" MIMO
     */
    public function test_get_mixed_mimo()
    {
        $p = new MimoParser();

        $mimo = $p->getModelsFromData($this->getComponentData([
            'type' => "mixed",
            'value' => [2, 4]
        ]), 'mimoDl', false);

        $mimo = $mimo->map(
            static fn (Mimo $item) => Arr::except($item->toArray(), ['id'])
        );

        $this->assertEquals([
            [
                'mimo' => 2,
                'is_ul' => false,
            ],
            [
                'mimo' => 4,
                'is_ul' => false,
            ]
        ], $mimo->toArray());
    }
}
