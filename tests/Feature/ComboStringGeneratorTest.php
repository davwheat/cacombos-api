<?php

namespace Tests\Feature;

use App\DataParser\Generators\ComboStringGenerator;
use App\Models\LteComponent;
use App\Models\Mimo;
use App\Models\NrComponent;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboStringGeneratorTest extends TestCase
{
    use RefreshDatabase;
    use ArraySubsetAsserts;

    protected $seed = true;

    protected static $auth = [
        'x-auth-token' => 'admin',
    ];

    /**
     * Creates combo string for LTE only combo with mimo.
     */
    public function test_generates_combo_for_lte_only_with_mimo()
    {
        $g = new ComboStringGenerator();

        $m4d = Mimo::firstOrCreate(['mimo' => 4, 'is_ul' => false]);
        $m2d = Mimo::firstOrCreate(['mimo' => 2, 'is_ul' => false]);
        $m2u = Mimo::firstOrCreate(['mimo' => 2, 'is_ul' => true]);
        $m1u = Mimo::firstOrCreate(['mimo' => 1, 'is_ul' => true]);

        // 1C4A
        $c1 = new LteComponent([
            'band'            => 1,
            'dl_class'        => 'C',
            'ul_class'        => 'A',
            'component_index' => 1,
        ]);
        $c1->save();
        $c1->mimos()->sync([
            $m4d->id,
            $m1u->id,
        ]);

        // 3A4
        $c2 = new LteComponent([
            'band'            => 3,
            'dl_class'        => 'A',
            'component_index' => 2,
        ]);
        $c2->save();
        $c2->mimos()->sync([
            $m4d->id,
            $m2d->id,
        ]);

        $components = [
            $c1,
            $c2,
        ];

        $comboString = $g->getComboStringFromComponents($components);

        $this->assertEquals('3A4-1C4A', $comboString);
    }

    /**
     * Creates combo string for NR only combo with mimo.
     */
    public function test_generates_combo_for_nr_only_with_mimo()
    {
        $g = new ComboStringGenerator();

        $m4d = Mimo::firstOrCreate(['mimo' => 4, 'is_ul' => false]);
        $m2d = Mimo::firstOrCreate(['mimo' => 2, 'is_ul' => false]);
        $m2u = Mimo::firstOrCreate(['mimo' => 2, 'is_ul' => true]);
        $m1u = Mimo::firstOrCreate(['mimo' => 1, 'is_ul' => true]);

        // 1C4A
        $c1 = new NrComponent([
            'band'            => 1,
            'dl_class'        => 'C',
            'ul_class'        => 'A',
            'component_index' => 1,
        ]);
        $c1->save();
        $c1->mimos()->sync([
            $m4d->id,
            $m1u->id,
            $m2u->id,
        ]);

        // 3A4
        $c2 = new NrComponent([
            'band'            => 3,
            'dl_class'        => 'A',
            'component_index' => 2,
        ]);
        $c2->save();
        $c2->mimos()->sync([
            $m2d->id,
            $m4d->id,
        ]);

        $components = [
            $c1,
            $c2,
        ];

        $comboString = $g->getComboStringFromComponents($components);

        $this->assertEquals('n3A4-n1C4A2', $comboString);
    }

    /**
     * Creates combo string for ENDC combo with mimo.
     */
    public function test_generates_combo_for_endc_with_mimo()
    {
        $g = new ComboStringGenerator();

        /** @var Mimo */
        $m4d = Mimo::firstOrCreate(['mimo' => 4, 'is_ul' => 0]);
        /** @var Mimo */
        $m2d = Mimo::firstOrCreate(['mimo' => 2, 'is_ul' => 0]);
        /** @var Mimo */
        $m2u = Mimo::firstOrCreate(['mimo' => 2, 'is_ul' => 1]);
        /** @var Mimo */
        $m1u = Mimo::firstOrCreate(['mimo' => 1, 'is_ul' => 1]);

        // 28A4A
        $c1 = new LteComponent([
            'band'            => 28,
            'dl_class'        => 'A',
            'ul_class'        => 'A',
            'component_index' => 1,
        ]);
        $c1->save();
        $c1->mimos()->sync([
            $m4d->id,
            $m1u->id,
        ]);

        // 7C
        $c2 = new LteComponent([
            'band'            => 7,
            'dl_class'        => 'C',
            'component_index' => 2,
        ]);
        $c2->save();
        $c2->mimos()->sync([
            $m4d->id,
            $m2d->id,
        ]);

        // n1C4A2
        $c3 = new NrComponent([
            'band'            => 1,
            'dl_class'        => 'C',
            'ul_class'        => 'A',
            'component_index' => 3,
        ]);
        $c3->save();
        $c3->mimos()->sync([
            $m4d->id,
            $m1u->id,
            $m2u->id,
        ]);

        // n3A4
        $c4 = new NrComponent([
            'band'            => 3,
            'dl_class'        => 'A',
            'component_index' => 4,
        ]);
        $c4->save();
        $c4->mimos()->sync([
            $m2d->id,
            $m4d->id,
        ]);

        $components = [
            $c1,
            $c2,
            $c3,
            $c4,
        ];

        $comboString = $g->getComboStringFromComponents($components);

        $this->assertEquals('28A4A-7C4_n3A4-n1C4A2', $comboString);
    }
}
