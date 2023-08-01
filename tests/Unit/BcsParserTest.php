<?php

namespace Tests\Unit\DataParser;

use App\DataParser\ElementParser\BcsParser;
use Tests\UnitTestCase;

class BcsParserTest extends UnitTestCase
{
    /**
     * Gets correct value for "all" BCS
     */
    public function test_get_all_bcs()
    {
        $p = new BcsParser();

        $bcs = $p->getBcsFromData([
            'components' => [],
            'bcs' => [
                'type' => 'all',
            ],
        ], 'bcs');

        $this->assertEquals(['all'], $bcs);
    }

    /**
     * Get correct value for multi BCS
     */
    public function test_get_multi_bcs()
    {
        $p = new BcsParser();

        $bcs = $p->getBcsFromData([
            'components' => [],
            'bcs' => [
                'type' => 'multi',
                'value' => ['a', 'b', 'c'],
            ],
        ], 'bcs');

        $this->assertEquals(['a', 'b', 'c'], $bcs);
    }

    /**
     * Get correct value for single BCS
     */
    public function test_get_single_bcs()
    {
        $p = new BcsParser();

        $bcs = $p->getBcsFromData([
            'components' => [],
            'bcs' => [
                'type' => 'single',
                'value' => 'a',
            ],
        ], 'bcs');

        $this->assertEquals(['a'], $bcs);
    }

    /**
     * Get correct value for empty BCS
     */
    public function test_get_empty_bcs()
    {
        $p = new BcsParser();

        $bcs = $p->getBcsFromData([
            'components' => [],
            'bcs' => [
                'type' => 'empty',
            ],
        ], 'bcs');

        $this->assertNull($bcs);
    }
}
