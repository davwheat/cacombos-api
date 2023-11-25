<?php

namespace Tests\Unit;

use App\DataParser\Generators\ComboStringGenerator;
use App\Models\LteComponent;
use App\Models\NrComponent;
use Tests\UnitTestCase;

class ComboStringGeneratorTest extends UnitTestCase
{
    /**
     * Creates combo string for LTE only combo.
     */
    public function test_generates_combo_for_lte_only_with_no_mimo()
    {
        $g = new ComboStringGenerator();

        $components = [
            new LteComponent([
                'band'     => 1,
                'dl_class' => 'C',
                'ul_class' => 'A',
            ]),
            new LteComponent([
                'band'     => 2,
                'dl_class' => 'C',
            ]),
            new LteComponent([
                'band'     => 3,
                'dl_class' => 'A',
            ]),
        ];

        $comboString = $g->getComboStringFromComponents($components);

        $this->assertEquals('3A-2C-1CA', $comboString);
    }

    /**
     * Creates combo string for NR only combo.
     */
    public function test_generates_combo_for_nr_only_with_no_mimo()
    {
        $g = new ComboStringGenerator();

        $components = [
            new NrComponent([
                'band'     => 1,
                'dl_class' => 'C',
                'ul_class' => 'A',
            ]),
            new NrComponent([
                'band'     => 2,
                'dl_class' => 'C',
            ]),
            new NrComponent([
                'band'     => 3,
                'dl_class' => 'A',
            ]),
        ];

        $comboString = $g->getComboStringFromComponents($components);

        $this->assertEquals('n3A-n2C-n1CA', $comboString);
    }

    /**
     * Creates combo string for ENDC combo.
     */
    public function test_generates_combo_for_endc_with_no_mimo()
    {
        $g = new ComboStringGenerator();

        $components = [
            new NrComponent([
                'band'     => 1,
                'dl_class' => 'C',
                'ul_class' => 'A',
            ]),
            new NrComponent([
                'band'     => 2,
                'dl_class' => 'C',
            ]),
            new LteComponent([
                'band'     => 7,
                'dl_class' => 'A',
                'ul_class' => 'A',
            ]),
            new NrComponent([
                'band'     => 3,
                'dl_class' => 'A',
            ]),
            new LteComponent([
                'band'     => 28,
                'dl_class' => 'A',
                'ul_class' => 'A',
            ]),
        ];

        $comboString = $g->getComboStringFromComponents($components);

        $this->assertEquals('28AA-7AA_n3A-n2C-n1CA', $comboString);
    }
}
