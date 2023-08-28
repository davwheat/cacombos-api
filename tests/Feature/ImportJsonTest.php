<?php

namespace Tests\Feature;

use App\Models\CapabilitySet;
use App\Models\Combo;
use App\Models\Device;
use App\Models\LteComponent;
use App\Models\Mimo;
use App\Models\Modulation;
use App\Models\SupportedLteBand;
use App\Models\SupportedNrBand;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ImportJsonTest extends TestCase
{
    use RefreshDatabase;
    use ArraySubsetAsserts;

    protected $seed = true;

    protected static $auth = [
        'x-auth-token' => 'admin',
    ];

    protected static $lte_ca_data = [
        'lteca' => [
            [
                'components' => [
                    [
                        'band'      => 1,
                        'bwClassDl' => 'A',
                        'bwClassUl' => 'A',
                        'mimoDl'    => [
                            'type'  => 'single',
                            'value' => 4,
                        ],
                        'mimoUl' => [
                            'type'  => 'single',
                            'value' => 1,
                        ],
                        'modulationDl' => [
                            'type'  => 'single',
                            'value' => 'qam256',
                        ],
                        'modulationUl' => [
                            'type'  => 'single',
                            'value' => 'qam64',
                        ],
                    ],
                ],
                'bcs' => [
                    'type'  => 'single',
                    'value' => 0,
                ],
            ],
            [
                'components' => [
                    [
                        'band'      => 3,
                        'bwClassDl' => 'A',
                        'bwClassUl' => 'A',
                        'mimoDl'    => [
                            'type'  => 'single',
                            'value' => 4,
                        ],
                        'mimoUl' => [
                            'type'  => 'single',
                            'value' => 1,
                        ],
                        'modulationDl' => [
                            'type'  => 'single',
                            'value' => 'qam256',
                        ],
                        'modulationUl' => [
                            'type'  => 'single',
                            'value' => 'qam64',
                        ],
                    ],
                ],
            ],
            [
                'components' => [
                    [
                        'band'      => 7,
                        'bwClassDl' => 'C',
                        'bwClassUl' => 'C',
                        'mimoDl'    => [
                            'type'  => 'mixed',
                            'value' => [2, 4],
                        ],
                        'mimoUl' => [
                            'type'  => 'mixed',
                            'value' => [1, 2],
                        ],
                        'modulationDl' => [
                            'type'  => 'mixed',
                            'value' => ['qam256', 'qam1024'],
                        ],
                        'modulationUl' => [
                            'type'  => 'mixed',
                            'value' => ['qam64', 'qam256'],
                        ],
                    ],
                ],
                'bcs' => [
                    'type'  => 'multi',
                    'value' => [1, 2, 3],
                ],
            ],
            [
                'components' => [
                    [
                        'band'      => 1,
                        'bwClassDl' => 'A',
                        'bwClassUl' => 'A',
                        'mimoDl'    => [
                            'type'  => 'single',
                            'value' => 4,
                        ],
                        'mimoUl' => [
                            'type'  => 'single',
                            'value' => 1,
                        ],
                        'modulationDl' => [
                            'type'  => 'single',
                            'value' => 'qam256',
                        ],
                        'modulationUl' => [
                            'type'  => 'single',
                            'value' => 'qam64',
                        ],
                    ],
                ],
                'bcs' => [
                    'type'  => 'all',
                ],
            ],
            [
                'components' => [
                    [
                        'band'      => 1,
                        'bwClassDl' => 'A',
                        'bwClassUl' => 'A',
                        'mimoDl'    => [
                            'type'  => 'single',
                            'value' => 4,
                        ],
                        'mimoUl' => [
                            'type'  => 'single',
                            'value' => 2,
                        ],
                        'modulationDl' => [
                            'type'  => 'single',
                            'value' => 'qam256',
                        ],
                        'modulationUl' => [
                            'type'  => 'single',
                            'value' => 'qam64',
                        ],
                    ],
                    [
                        'band'      => 3,
                        'mimoDl'    => [
                            'type'  => 'empty',
                        ],
                        'mimoUl' => [
                            'type'  => 'empty',
                        ],
                    ],
                    [
                        'band'         => 32,
                        'bwClassDl'    => 'A',
                        'modulationDl' => [
                            'type'  => 'empty',
                        ],
                    ],
                ],
                'bcs' => [
                    'type'  => 'empty',
                ],
            ],
        ],
    ];

    protected static $endc_data = [
        'endc' => [
            [
                'componentsLte' => [
                    [
                        'band'         => 66,
                        'bwClassDl'    => 'A',
                        'bwClassUl'    => 'A',
                        'mimoDl'       => ['type' => 'single', 'value' => 4],
                        'mimoUl'       => ['type' => 'single', 'value' => 1],
                        'modulationUl' => ['type' => 'single', 'value' => 'qam256'],
                    ],
                    [
                        'band'      => 66,
                        'bwClassDl' => 'A',
                        'mimoDl'    => ['type' => 'single', 'value' => 2],
                    ],
                    [
                        'band'      => 13,
                        'bwClassDl' => 'A',
                        'mimoDl'    => ['type' => 'single', 'value' => 2],
                    ],
                ],
                'componentsNr' => [
                    [
                        'band'         => 261,
                        'bwClassDl'    => 'G',
                        'bwClassUl'    => 'G',
                        'mimoDl'       => ['type' => 'single', 'value' => 2],
                        'mimoUl'       => ['type' => 'single', 'value' => 2],
                        'modulationUl' => ['type' => 'single', 'value' => 'qam256'],
                        'maxBw'        => 100,
                        'maxScs'       => 120,
                    ],
                    [
                        'band'      => 261,
                        'bwClassDl' => 'A',
                        'mimoDl'    => ['type' => 'single', 'value' => 2],
                        'maxBw'     => 100,
                        'maxScs'    => 120,
                    ],
                    [
                        'band'             => 261,
                        'mimoDl'           => ['type' => 'mixed', 'value' => [2, 4]],
                        'bw90mhzSupported' => true,
                    ],
                ],
                'bcsEutra' => [
                    'type' => 'all',
                ],
                'bcsNr' => [
                    'type' => 'all',
                ],
                'bcsIntraEndc' => [
                    'type' => 'all',
                ],
            ],
            [
                'componentsLte' => [
                    [
                        'band'      => 66,
                        'bwClassDl' => 'A',
                        'mimoDl'    => ['type' => 'single', 'value' => 4],
                    ],
                    [
                        'band'      => 66,
                        'bwClassDl' => 'A',
                        'mimoDl'    => ['type' => 'single', 'value' => 2],
                    ],
                    [
                        'band'         => 13,
                        'bwClassDl'    => 'A',
                        'bwClassUl'    => 'A',
                        'mimoDl'       => ['type' => 'single', 'value' => 2],
                        'mimoUl'       => ['type' => 'single', 'value' => 1],
                        'modulationUl' => ['type' => 'single', 'value' => 'qam256'],
                    ],
                ],
                'componentsNr' => [
                    [
                        'band'         => 261,
                        'bwClassDl'    => 'G',
                        'bwClassUl'    => 'G',
                        'mimoDl'       => ['type' => 'single', 'value' => 2],
                        'mimoUl'       => ['type' => 'single', 'value' => 2],
                        'modulationUl' => ['type' => 'single', 'value' => 'qam256'],
                        'maxBw'        => 100,
                        'maxScs'       => 120,
                    ],
                    [
                        'band'      => 261,
                        'bwClassDl' => 'A',
                        'mimoDl'    => ['type' => 'single', 'value' => 2],
                        'maxBw'     => 100,
                        'maxScs'    => 120,
                    ],
                ],
                'bcsEutra' => [
                    'type'  => 'single',
                    'value' => 1,
                ],
                'bcsNr' => [
                    'type'  => 'multi',
                    'value' => [1, 2, 3],
                ],
                'bcsIntraEndc' => [
                    'type' => 'empty',
                ],
            ],
        ],
    ];

    protected static $nrca_data = [
        'nrca' => [
            [
                'componentsNr' => [
                    [
                        'band'             => 78,
                        'bwClassDl'        => 'A',
                        'bwClassUl'        => 'A',
                        'mimoDl'           => ['type' => 'single', 'value' => 4],
                        'mimoUl'           => ['type' => 'single', 'value' => 1],
                        'modulationUl'     => ['type' => 'single', 'value' => 'qam256'],
                        'modulationDl'     => ['type' => 'single', 'value' => 'qam1024'],
                        'maxBw'            => 100,
                        'maxScs'           => 60,
                        'bw90mhzSupported' => true,
                    ],
                    [
                        'band'             => 78,
                        'bwClassDl'        => 'A',
                        'mimoDl'           => ['type' => 'mixed', 'value' => [4, 2]],
                        'bw90mhzSupported' => true,
                    ],
                    [
                        'band'         => 28,
                        'bwClassDl'    => 'A',
                        'mimoDl'       => ['type' => 'single', 'value' => 2],
                        'maxBw'        => 40,
                        'maxScs'       => 30,
                    ],
                ],
                'bcs' => [
                    'type' => 'all',
                ],
            ],
            [
                'componentsNr' => [
                    [
                        'band'             => 78,
                        'bwClassDl'        => 'A',
                        'bwClassUl'        => 'A',
                        'mimoDl'           => ['type' => 'single', 'value' => 4],
                        'mimoUl'           => ['type' => 'single', 'value' => 1],
                        'modulationUl'     => ['type' => 'single', 'value' => 'qam256'],
                        'maxBw'            => 100,
                        'maxScs'           => 60,
                        'bw90mhzSupported' => false,
                    ],
                ],
                'bcs' => [
                    'type' => 'empty',
                ],
            ],
        ],
    ];

    protected static $nrdc_data = [
        'nrdc' => [
            [
                'componentsFr1' => [
                    [
                        'band'             => 78,
                        'bwClassDl'        => 'A',
                        'bwClassUl'        => 'A',
                        'mimoDl'           => ['type' => 'single', 'value' => 4],
                        'mimoUl'           => ['type' => 'single', 'value' => 2],
                        'modulationUl'     => ['type' => 'single', 'value' => 'qam256'],
                        'modulationDl'     => ['type' => 'single', 'value' => 'qam256'],
                        'maxBw'            => 100,
                        'maxScs'           => 60,
                        'bw90mhzSupported' => true,
                    ],
                ],
                'componentsFr2' => [
                    [
                        'band'         => 261,
                        'bwClassDl'    => 'G',
                        'bwClassUl'    => 'G',
                        'mimoDl'       => ['type' => 'single', 'value' => 2],
                        'mimoUl'       => ['type' => 'single', 'value' => 1],
                        'modulationUl' => ['type' => 'single', 'value' => 'qam256'],
                        'maxBw'        => 400,
                        'maxScs'       => 120,
                    ],
                ],
                'bcs' => [
                    'type' => 'all',
                ],
            ],
        ],
    ];

    protected static $lte_bands = [
        'lteBands' => [
            [
                'band'      => 20,
                'mimoDl'    => [
                    'type'  => 'single',
                    'value' => 4,
                ],
                'mimoUl' => [
                    'type'  => 'single',
                    'value' => 1,
                ],
                'modulationDl' => [
                    'type'  => 'single',
                    'value' => 'qam256',
                ],
                'modulationUl' => [
                    'type'  => 'single',
                    'value' => 'qam64',
                ],
                'powerClass' => 'none',
            ],
            [
                'band'      => 41,
                'mimoDl'    => [
                    'type'  => 'single',
                    'value' => 4,
                ],
                'mimoUl' => [
                    'type'  => 'single',
                    'value' => 1,
                ],
                'modulationDl' => [
                    'type'  => 'single',
                    'value' => 'qam1024',
                ],
                'modulationUl' => [
                    'type'  => 'single',
                    'value' => 'qam256',
                ],
                'powerClass' => 'pc1dot5',
            ],
        ],
    ];

    protected static $nr_bands = [
        'nrNsaBandsEutra' => [
            [
                'band'   => 1,
                'mimoDl' => [
                    'type'  => 'single',
                    'value' => 4,
                ],
                'mimoUl' => [
                    'type'  => 'single',
                    'value' => 1,
                ],
            ],
        ],
        'nrSaBandsEutra' => [
            [
                'band'         => 7,
                'modulationDl' => [
                    'type'  => 'single',
                    'value' => 'qam1024',
                ],
                'modulationUl' => [
                    'type'  => 'single',
                    'value' => 'qam1024',
                ],
            ],
        ],
        'nrBands' => [
            [
                'band'         => 1,
                'modulationDl' => [
                    'type'  => 'single',
                    'value' => 'qam256',
                ],
                'modulationUl' => [
                    'type'  => 'single',
                    'value' => 'qam256',
                ],
                'maxUplinkDutyCycle' => 1,
                'powerClass'         => 'pc3',
                'bandwidths'         => [
                    [
                        'scs'          => 15,
                        'bandwidthsDl' => [50, 40, 30, 25, 20, 15, 10, 5],
                        'bandwidthsUl' => [50, 40, 30, 25, 20, 15, 10, 5],
                    ],
                ],
                'rateMatchingLteCrs' => true,
            ],
            [
                'band'         => 3,
                'modulationDl' => [
                    'type'  => 'single',
                    'value' => 'qam256',
                ],
                'modulationUl' => [
                    'type'  => 'single',
                    'value' => 'qam256',
                ],
                'bandwidths' => [
                    [
                        'scs'          => 15,
                        'bandwidthsDl' => [40, 30, 25, 20, 15, 10, 5],
                        'bandwidthsUl' => [40, 30, 25, 20, 15, 10, 5],
                    ],
                ],
                'rateMatchingLteCrs' => true,
            ],
            [
                'band'         => 7,
                'modulationDl' => [
                    'type'  => 'single',
                    'value' => 'qam256',
                ],
                'modulationUl' => [
                    'type'  => 'single',
                    'value' => 'qam256',
                ],
                'bandwidths' => [
                    [
                        'scs'          => 15,
                        'bandwidthsDl' => [50, 40, 30, 25, 20, 15, 10, 5],
                        'bandwidthsUl' => [50, 40, 30, 25, 20, 15, 10, 5],
                    ],
                ],
                'rateMatchingLteCrs' => true,
            ],
        ],
    ];

    /**
     * Cannot parse a log without any data.
     */
    public function test_cannot_parse_log_without_data(): void
    {
        $response = $this->post('/v1/actions/import-json', [], ImportJsonTest::$auth);

        $response->assertStatus(422);
        $response->assertJson(['errors' => ['jsonData' => [], 'deviceId' => [], 'capabilitySetId' => []]]);
        $this->assertStringContainsString('field is required', $response->getContent() ?: '');
    }

    /**
     * Cannot parse a log without a valid device ID.
     */
    public function test_cannot_parse_log_with_invalid_device_id(): void
    {
        $response = $this->post('/v1/actions/import-json', ['jsonData' => 'test', 'deviceId' => Device::first()->id, 'capabilitySetId' => 99999999], ImportJsonTest::$auth);

        $response->assertStatus(422);
        $response->assertJson(['errors' => ['capabilitySetId' => []]]);
        $this->assertStringContainsString('capability set id is invalid', $response->getContent() ?: '');
    }

    /**
     * Cannot parse a log without a valid capability set ID.
     */
    public function test_cannot_parse_log_with_invalid_capability_set_id(): void
    {
        $response = $this->post('/v1/actions/import-json', ['jsonData' => 'test', 'deviceId' => 99999999, 'capabilitySetId' => CapabilitySet::first()->id], ImportJsonTest::$auth);

        $response->assertStatus(422);
        $response->assertJson(['errors' => ['deviceId' => []]]);
        $this->assertStringContainsString('device id is invalid', $response->getContent() ?: '');
    }

    /**
     * Can import a LTE categories from JSON output.
     */
    public function test_imports_lte_category(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/import-json', [
            'jsonData' => json_encode([
                'lteCategoryDl' => 14,
                'lteCategoryUl' => 2,
            ]), 'deviceId' => $testingDevice->id, 'capabilitySetId' => $testingCapabilitySet->id,
        ], ImportJsonTest::$auth);

        $response->assertStatus(200);
        $this->assertSame('null', $response->getContent());

        $testingCapabilitySet->refresh();

        $this->assertSame($testingCapabilitySet->lte_category_dl, 14);
        $this->assertSame($testingCapabilitySet->lte_category_ul, 2);
    }

    /**
     * Can import a LTE categories from JSON output.
     */
    public function test_imports_blank_lte_category(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/import-json', [
            'jsonData' => json_encode([
                // Placeholder data
                'lteca' => [],
            ]), 'deviceId' => $testingDevice->id, 'capabilitySetId' => $testingCapabilitySet->id,
        ], ImportJsonTest::$auth);

        $response->assertStatus(200);
        $this->assertSame('null', $response->getContent());

        $testingCapabilitySet->refresh();

        $this->assertSame($testingCapabilitySet->lte_category_dl, null);
        $this->assertSame($testingCapabilitySet->lte_category_ul, null);
    }

    /**
     * Can import a valid lte ca data JSON output.
     */
    public function test_imports_lte_ca_data(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/import-json', ['jsonData' => json_encode(ImportJsonTest::$lte_ca_data), 'deviceId' => $testingDevice->id, 'capabilitySetId' => $testingCapabilitySet->id], ImportJsonTest::$auth);

        $response->assertStatus(200);
        $this->assertSame('null', $response->getContent());

        $testingCapabilitySet->refresh();
        $combos = $testingCapabilitySet->combos;

        $this->assertSame(5, $combos->count());

        // ##############################
        // Combo 1
        // ##############################

        /** @var Combo */
        $combo = $combos->get(0);

        $this->assertArraySubset([
            'combo_string'      => '1A4A',
            'capability_set_id' => $testingCapabilitySet->id,
        ], $combo->getAttributes());
        $this->assertSame([0], $combo->bandwidth_combination_set_eutra);

        $comboComponents = $combo->lteComponents;
        $this->assertSame(1, $comboComponents->count());

        $this->assertSame(Arr::except($comboComponents->first()->getAttributes(), 'id'), [
            'band'            => 1,
            'dl_class'        => 'A',
            'ul_class'        => 'A',
            'component_index' => 0,
        ]);

        /** @var LteComponent */
        $cc = $comboComponents->first();

        $this->assertSame($cc->dl_mimos()->count(), 1);
        $this->assertSame($cc->ul_mimos()->count(), 1);

        $this->assertSame(4, $cc->dl_mimos()->first()->mimo);
        $this->assertSame(1, $cc->ul_mimos()->first()->mimo);

        // ##############################
        // Combo 2
        // ##############################

        /** @var Combo */
        $combo = $combos->get(1);

        $this->assertArraySubset([
            'combo_string'      => '3A4A',
            'capability_set_id' => $testingCapabilitySet->id,
        ], $combo->getAttributes());
        $this->assertSame([], $combo->bandwidth_combination_set_eutra);

        $comboComponents = $combo->lteComponents;
        $this->assertSame(1, $comboComponents->count());

        $this->assertSame(Arr::except($comboComponents->first()->getAttributes(), 'id'), [
            'band'            => 3,
            'dl_class'        => 'A',
            'ul_class'        => 'A',
            'component_index' => 0,
        ]);

        // ##############################
        // Combo 3
        // ##############################

        /** @var Combo */
        $combo = $combos->get(2);

        $this->assertArraySubset([
            'combo_string'      => '7C4C2',
            'capability_set_id' => $testingCapabilitySet->id,
        ], $combo->getAttributes());
        $this->assertSame([1, 2, 3], $combo->bandwidth_combination_set_eutra);

        $comboComponents = $combo->lteComponents;
        $this->assertSame(1, $comboComponents->count());

        $this->assertSame(Arr::except($comboComponents->first()->getAttributes(), 'id'), [
            'band'            => 7,
            'dl_class'        => 'C',
            'ul_class'        => 'C',
            'component_index' => 0,
        ]);

        /** @var LteComponent */
        $cc = $comboComponents->first();

        // Mimos

        $this->assertSame($cc->dl_mimos()->count(), 2);
        $this->assertSame($cc->ul_mimos()->count(), 2);

        $dlMimos = $cc->dl_mimos()->get()->map(function (Mimo $mimo) {
            return $mimo->mimo;
        })->values()->toArray();
        $this->assertEqualsCanonicalizing([2, 4], $dlMimos);

        $ulMimos = $cc->ul_mimos()->get()->map(function (Mimo $mimo) {
            return $mimo->mimo;
        })->values()->toArray();
        $this->assertEqualsCanonicalizing([1, 2], $ulMimos);

        // Modulation

        $this->assertSame($cc->dl_modulations()->count(), 2);
        $this->assertSame($cc->ul_modulations()->count(), 2);

        $dlMimos = $cc->dl_modulations()->get()->map(function (Modulation $mod) {
            return $mod->modulation;
        })->values()->toArray();
        $this->assertEqualsCanonicalizing(['qam256', 'qam1024'], $dlMimos);

        $ulMimos = $cc->ul_modulations()->get()->map(function (Modulation $mod) {
            return $mod->modulation;
        })->values()->toArray();
        $this->assertEqualsCanonicalizing(['qam64', 'qam256'], $ulMimos);

        // ##############################
        // Combo 4
        // ##############################

        /** @var Combo */
        $combo = $combos->get(3);

        $this->assertArraySubset([
            'combo_string'      => '1A4A',
            'capability_set_id' => $testingCapabilitySet->id,
        ], $combo->getAttributes());
        $this->assertSame(['all'], $combo->bandwidth_combination_set_eutra);

        $comboComponents = $combo->lteComponents;
        $this->assertSame(1, $comboComponents->count());

        $this->assertSame(Arr::except($comboComponents->first()->getAttributes(), 'id'), [
            'band'            => 1,
            'dl_class'        => 'A',
            'ul_class'        => 'A',
            'component_index' => 0,
        ]);

        /** @var LteComponent */
        $cc = $comboComponents->first();

        // Modulation

        $this->assertSame($cc->dl_modulations()->count(), 1);
        $this->assertSame($cc->ul_modulations()->count(), 1);

        $dlMimos = $cc->dl_modulations()->get()->map(function (Modulation $mod) {
            return $mod->modulation;
        })->values()->toArray();
        $this->assertEqualsCanonicalizing(['qam256'], $dlMimos);

        $ulMimos = $cc->ul_modulations()->get()->map(function (Modulation $mod) {
            return $mod->modulation;
        })->values()->toArray();
        $this->assertEqualsCanonicalizing(['qam64'], $ulMimos);

        // ##############################
        // Combo 5
        // ##############################

        /** @var Combo */
        $combo = $combos->get(4);

        $this->assertArraySubset([
            'combo_string'      => '32A-3-1A4A2',
            'capability_set_id' => $testingCapabilitySet->id,
        ], $combo->getAttributes());
        $this->assertSame(null, $combo->bandwidth_combination_set_eutra);

        $comboComponents = $combo->lteComponents;
        $this->assertSame(3, $comboComponents->count());

        $this->assertSame(Arr::except($comboComponents->get(0)->getAttributes(), 'id'), [
            'band'            => 1,
            'dl_class'        => 'A',
            'ul_class'        => 'A',
            'component_index' => 0,
        ]);

        $this->assertSame(Arr::except($comboComponents->get(1)->getAttributes(), 'id'), [
            'band'            => 3,
            'dl_class'        => null,
            'ul_class'        => null,
            'component_index' => 1,
        ]);

        $this->assertSame(Arr::except($comboComponents->get(2)->getAttributes(), 'id'), [
            'band'            => 32,
            'dl_class'        => 'A',
            'ul_class'        => null,
            'component_index' => 2,
        ]);

        /** @var LteComponent */
        $cc = $comboComponents->get(1);

        // Mimos

        $this->assertSame($cc->dl_mimos()->count(), 0);
        $this->assertSame($cc->ul_mimos()->count(), 0);

        // Modulation

        $this->assertSame($cc->dl_modulations()->count(), 0);
        $this->assertSame($cc->ul_modulations()->count(), 0);

        /** @var LteComponent */
        $cc = $comboComponents->get(2);

        // Mimos

        $this->assertSame($cc->dl_mimos()->count(), 0);
        $this->assertSame($cc->ul_mimos()->count(), 0);

        // Modulation

        $this->assertSame($cc->dl_modulations()->count(), 0);
        $this->assertSame($cc->ul_modulations()->count(), 0);
    }

    /**
     * Can import a valid ENDC data JSON output.
     */
    public function test_imports_endc_data(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/import-json', ['jsonData' => json_encode(ImportJsonTest::$endc_data), 'deviceId' => $testingDevice->id, 'capabilitySetId' => $testingCapabilitySet->id], ImportJsonTest::$auth);

        $response->assertStatus(200);
        $this->assertSame('null', $response->getContent());

        $testingCapabilitySet->refresh();
        $combos = $testingCapabilitySet->combos;

        $this->assertSame(2, $combos->count());

        // ##############################
        // Combo 1
        // ##############################

        /** @var Combo */
        $combo = $combos->get(0);

        $this->assertArraySubset([
            'combo_string'      => '66A4A-66A2-13A2_n261G2G2-n261A2-n261X4',
            'capability_set_id' => $testingCapabilitySet->id,
        ], $combo->getAttributes());
        $this->assertSame(['all'], $combo->bandwidth_combination_set_eutra);
        $this->assertSame(['all'], $combo->bandwidth_combination_set_nr);
        $this->assertSame(['all'], $combo->bandwidth_combination_set_intra_endc);

        // LTE components
        $lteComboComponents = $combo->lteComponents;
        $this->assertSame(3, $lteComboComponents->count());

        $this->assertSame(Arr::except($lteComboComponents->first()->getAttributes(), 'id'), [
            'band'            => 66,
            'dl_class'        => 'A',
            'ul_class'        => 'A',
            'component_index' => 0,
        ]);

        /** @var LteComponent */
        $cc = $lteComboComponents->first();

        $this->assertSame($cc->dl_mimos()->count(), 1);
        $this->assertSame($cc->ul_mimos()->count(), 1);

        $this->assertSame(4, $cc->dl_mimos()->first()->mimo);
        $this->assertSame(1, $cc->ul_mimos()->first()->mimo);

        $this->assertSame($cc->ul_modulations()->count(), 1);
        $this->assertSame($cc->dl_modulations()->count(), 0);

        $this->assertSame('qam256', $cc->ul_modulations()->first()->modulation);

        // NR components
        $nrComboComponents = $combo->nrComponents;
        $this->assertSame(3, $nrComboComponents->count());

        /** @var NrComponent */
        $cc = $nrComboComponents->get(0);

        $this->assertSame(Arr::except($cc->getAttributes(), 'id'), [
            'band'               => 261,
            'dl_class'           => 'G',
            'ul_class'           => 'G',
            'bandwidth'          => 100,
            'subcarrier_spacing' => 120,
            'component_index'    => 0,
            'supports_90mhz_bw'  => null,
        ]);

        $this->assertSame($cc->dl_mimos()->count(), 1);
        $this->assertSame($cc->ul_mimos()->count(), 1);

        $this->assertSame(2, $cc->dl_mimos()->first()->mimo);
        $this->assertSame(2, $cc->ul_mimos()->first()->mimo);

        $this->assertSame($cc->ul_modulations()->count(), 1);
        $this->assertSame($cc->dl_modulations()->count(), 0);

        $this->assertSame('qam256', $cc->ul_modulations()->first()->modulation);

        /** @var NrComponent */
        $cc = $nrComboComponents->get(2);

        $this->assertSame(Arr::except($cc->getAttributes(), 'id'), [
            'band'               => 261,
            'dl_class'           => null,
            'ul_class'           => null,
            'bandwidth'          => null,
            'subcarrier_spacing' => null,
            'component_index'    => 2,
            'supports_90mhz_bw'  => 1,
        ]);

        $this->assertSame($cc->dl_mimos()->count(), 2);
        $this->assertSame($cc->ul_mimos()->count(), 0);

        $dl_mimos = $cc->dl_mimos()->get()->pluck('mimo')->toArray();
        $this->assertEqualsCanonicalizing([2, 4], $dl_mimos);

        $this->assertSame($cc->ul_modulations()->count(), 0);
        $this->assertSame($cc->dl_modulations()->count(), 0);

        $this->assertSame($cc->supports_90mhz_bw, true);
        $this->assertSame($cc->max_bandwidth, null);
        $this->assertSame($cc->subcarrier_spacing, null);

        // ##############################
        // Combo 2
        // ##############################

        // ...
    }

    /**
     * Can import a valid NR CA data JSON output.
     */
    public function test_imports_nrca_data(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/import-json', ['jsonData' => json_encode(ImportJsonTest::$nrca_data), 'deviceId' => $testingDevice->id, 'capabilitySetId' => $testingCapabilitySet->id], ImportJsonTest::$auth);

        $response->assertStatus(200);
        $this->assertSame('null', $response->getContent());

        $testingCapabilitySet->refresh();
        $combos = $testingCapabilitySet->combos;

        $this->assertSame(2, $combos->count());

        // ##############################
        // Combo 1
        // ##############################

        /** @var Combo */
        $combo = $combos->get(0);

        $this->assertArraySubset([
            'combo_string'      => 'n78A4A-n78A4-n28A2',
            'capability_set_id' => $testingCapabilitySet->id,
        ], $combo->getAttributes());
        $this->assertSame(['all'], $combo->bandwidth_combination_set_nr);

        // NR components
        $nrComboComponents = $combo->nrComponents;
        $this->assertSame(3, $nrComboComponents->count());

        /** @var NrComponent */
        $cc = $nrComboComponents->get(0);

        $this->assertSame(Arr::except($cc->getAttributes(), 'id'), [
            'band'               => 78,
            'dl_class'           => 'A',
            'ul_class'           => 'A',
            'bandwidth'          => 100,
            'subcarrier_spacing' => 60,
            'component_index'    => 0,
            'supports_90mhz_bw'  => 1,
        ]);

        $this->assertSame($cc->dl_mimos()->count(), 1);
        $this->assertSame($cc->ul_mimos()->count(), 1);

        $this->assertSame(4, $cc->dl_mimos()->first()->mimo);
        $this->assertSame(1, $cc->ul_mimos()->first()->mimo);

        $this->assertSame($cc->ul_modulations()->count(), 1);
        $this->assertSame($cc->dl_modulations()->count(), 1);

        $this->assertSame('qam256', $cc->ul_modulations()->first()->modulation);
        $this->assertSame('qam1024', $cc->dl_modulations()->first()->modulation);

        /** @var NrComponent */
        $cc = $nrComboComponents->get(1);

        $this->assertSame(Arr::except($cc->getAttributes(), 'id'), [
            'band'               => 78,
            'dl_class'           => 'A',
            'ul_class'           => null,
            'bandwidth'          => null,
            'subcarrier_spacing' => null,
            'component_index'    => 1,
            'supports_90mhz_bw'  => 1,
        ]);

        $this->assertSame($cc->dl_mimos()->count(), 2);
        $this->assertSame($cc->ul_mimos()->count(), 0);

        $dl_mimos = $cc->dl_mimos()->get()->pluck('mimo')->toArray();
        $this->assertEqualsCanonicalizing([2, 4], $dl_mimos);

        $this->assertSame($cc->ul_modulations()->count(), 0);
        $this->assertSame($cc->dl_modulations()->count(), 0);

        /** @var NrComponent */
        $cc = $nrComboComponents->get(2);

        $this->assertSame(Arr::except($cc->getAttributes(), 'id'), [
            'band'               => 28,
            'dl_class'           => 'A',
            'ul_class'           => null,
            'bandwidth'          => 40,
            'subcarrier_spacing' => 30,
            'component_index'    => 2,
            'supports_90mhz_bw'  => null,
        ]);

        $this->assertSame($cc->dl_mimos()->count(), 1);
        $this->assertSame($cc->ul_mimos()->count(), 0);

        $this->assertSame(2, $cc->dl_mimos()->first()->mimo);

        $this->assertSame($cc->ul_modulations()->count(), 0);
        $this->assertSame($cc->dl_modulations()->count(), 0);

        // ##############################
        // Combo 2
        // ##############################

        // ...
    }

    /**
     * Can import a valid NR-DC data JSON output.
     */
    public function test_imports_nrdc_data(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/import-json', ['jsonData' => json_encode(ImportJsonTest::$nrdc_data), 'deviceId' => $testingDevice->id, 'capabilitySetId' => $testingCapabilitySet->id], ImportJsonTest::$auth);

        $response->assertStatus(200);
        $this->assertSame('null', $response->getContent());

        $testingCapabilitySet->refresh();
        $combos = $testingCapabilitySet->combos;

        $this->assertSame(1, $combos->count());

        // ##############################
        // Combo 1
        // ##############################

        /** @var Combo */
        $combo = $combos->get(0);

        $this->assertArraySubset([
            'combo_string'      => 'n261G2G-n78A4A2',
            'capability_set_id' => $testingCapabilitySet->id,
        ], $combo->getAttributes());
        $this->assertSame(['all'], $combo->bandwidth_combination_set_nr);

        // NR components
        $nrComboComponents = $combo->nrComponents;
        $this->assertSame(2, $nrComboComponents->count());

        /** @var NrComponent */
        $cc = $nrComboComponents->get(0);

        $this->assertSame(Arr::except($cc->getAttributes(), 'id'), [
            'band'               => 78,
            'dl_class'           => 'A',
            'ul_class'           => 'A',
            'bandwidth'          => 100,
            'subcarrier_spacing' => 60,
            'component_index'    => 0,
            'supports_90mhz_bw'  => 1,
        ]);

        $this->assertSame($cc->dl_mimos->count(), 1);
        $this->assertSame($cc->ul_mimos->count(), 1);

        $this->assertSame(4, $cc->dl_mimos->first()->mimo);
        $this->assertSame(2, $cc->ul_mimos->first()->mimo);

        $this->assertSame($cc->ul_modulations->count(), 1);
        $this->assertSame($cc->dl_modulations->count(), 1);

        $this->assertSame('qam256', $cc->ul_modulations->first()->modulation);
        $this->assertSame('qam256', $cc->dl_modulations->first()->modulation);

        /** @var NrComponent */
        $cc = $nrComboComponents->get(1);

        $this->assertSame(Arr::except($cc->getAttributes(), 'id'), [
            'band'               => 261,
            'dl_class'           => 'G',
            'ul_class'           => 'G',
            'bandwidth'          => 400,
            'subcarrier_spacing' => 120,
            'component_index'    => 1,
            'supports_90mhz_bw'  => null,
        ]);

        $this->assertSame($cc->dl_mimos->count(), 1);
        $this->assertSame($cc->ul_mimos->count(), 1);

        $this->assertSame(2, $cc->dl_mimos->first()->mimo);
        $this->assertSame(1, $cc->ul_mimos->first()->mimo);

        $this->assertSame($cc->ul_modulations->count(), 1);
        $this->assertSame($cc->dl_modulations->count(), 0);

        $this->assertSame('qam256', $cc->ul_modulations->first()->modulation);

        // ##############################
        // Combo 2
        // ##############################

        // ...
    }

    /**
     * Can import supported LTE bands from JSON output.
     */
    public function test_imports_lte_bands_data(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/import-json', ['jsonData' => json_encode(ImportJsonTest::$lte_bands), 'deviceId' => $testingDevice->id, 'capabilitySetId' => $testingCapabilitySet->id], ImportJsonTest::$auth);

        $response->assertStatus(200);
        $this->assertSame('null', $response->getContent());

        $testingCapabilitySet->refresh();
        $bands = $testingCapabilitySet->supportedLteBands;

        $this->assertSame(2, $bands->count());

        // ##############################
        // Band 1
        // ##############################

        /** @var SupportedLteBand */
        $band = $bands->get(0);

        $this->assertArraySubset([
            'band'        => 20,
            'power_class' => 'none',
        ], $band->getAttributes());

        $this->assertSame($band->dl_mimos->count(), 1);
        $this->assertSame($band->ul_mimos->count(), 1);

        $this->assertSame(4, $band->dl_mimos->first()->mimo);
        $this->assertSame(1, $band->ul_mimos->first()->mimo);

        $this->assertSame($band->dl_modulations->count(), 1);
        $this->assertSame($band->ul_modulations->count(), 1);

        $this->assertSame('qam256', $band->dl_modulations->first()->modulation);
        $this->assertSame('qam64', $band->ul_modulations->first()->modulation);

        // ##############################
        // Band 2
        // ##############################

        /** @var SupportedLteBand */
        $band = $bands->get(1);

        $this->assertArraySubset([
            'band'        => 41,
            'power_class' => 'pc1dot5',
        ], $band->getAttributes());

        $this->assertSame($band->dl_mimos->count(), 1);
        $this->assertSame($band->ul_mimos->count(), 1);

        $this->assertSame(4, $band->dl_mimos->first()->mimo);
        $this->assertSame(1, $band->ul_mimos->first()->mimo);

        $this->assertSame($band->dl_modulations->count(), 1);
        $this->assertSame($band->ul_modulations->count(), 1);

        $this->assertSame('qam1024', $band->dl_modulations->first()->modulation);
        $this->assertSame('qam256', $band->ul_modulations->first()->modulation);
    }

    /**
     * Can import supported NR bands from JSON output.
     */
    public function test_imports_nr_bands_data(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/import-json', ['jsonData' => json_encode(ImportJsonTest::$nr_bands), 'deviceId' => $testingDevice->id, 'capabilitySetId' => $testingCapabilitySet->id], ImportJsonTest::$auth);

        $response->assertStatus(200);
        $this->assertSame('null', $response->getContent());

        $testingCapabilitySet->refresh();
        $bands = $testingCapabilitySet->supportedNrBands;

        $this->assertSame(3, $bands->count());

        // ##############################
        // Band 1
        // ##############################

        /** @var SupportedNrBand */
        $band = $bands->get(0);

        $this->assertArraySubset([
            'band'                  => 1,
            'max_uplink_duty_cycle' => 1,
            'power_class'           => 'pc3',
            'rate_matching_lte_crs' => 1,
            'supports_endc'         => 1,
            'supports_sa'           => 0,
        ], $band->getAttributes());

        $this->assertSame($band->bandwidths, static::$nr_bands['nrBands'][0]['bandwidths']);

        $this->assertSame($band->dl_mimos->count(), 1);
        $this->assertSame($band->ul_mimos->count(), 1);

        $this->assertSame(4, $band->dl_mimos->first()->mimo);
        $this->assertSame(1, $band->ul_mimos->first()->mimo);

        $this->assertSame($band->dl_modulations->count(), 1);
        $this->assertSame($band->ul_modulations->count(), 1);

        $this->assertSame('qam256', $band->dl_modulations->first()->modulation);
        $this->assertSame('qam256', $band->ul_modulations->first()->modulation);

        // ##############################
        // Band 2
        // ##############################

        /** @var SupportedNrBand */
        $band = $bands->get(1);

        $this->assertArraySubset([
            'band'                  => 3,
            'max_uplink_duty_cycle' => null,
            'power_class'           => null,
            'rate_matching_lte_crs' => 1,
            'supports_endc'         => 0,
            'supports_sa'           => 0,
        ], $band->getAttributes());

        $this->assertSame($band->bandwidths, static::$nr_bands['nrBands'][1]['bandwidths']);

        $this->assertSame($band->dl_mimos->count(), 0);
        $this->assertSame($band->ul_mimos->count(), 0);

        $this->assertSame($band->dl_modulations->count(), 1);
        $this->assertSame($band->ul_modulations->count(), 1);

        $this->assertSame('qam256', $band->dl_modulations->first()->modulation);
        $this->assertSame('qam256', $band->ul_modulations->first()->modulation);

        // ##############################
        // Band 3
        // ##############################

        /** @var SupportedNrBand */
        $band = $bands->get(2);

        $this->assertArraySubset([
            'band'                  => 7,
            'max_uplink_duty_cycle' => null,
            'power_class'           => null,
            'rate_matching_lte_crs' => 1,
            'supports_endc'         => 0,
            'supports_sa'           => 1,
        ], $band->getAttributes());

        $this->assertSame($band->bandwidths, static::$nr_bands['nrBands'][2]['bandwidths']);

        $this->assertSame($band->dl_mimos->count(), 0);
        $this->assertSame($band->ul_mimos->count(), 0);

        $this->assertSame($band->dl_modulations->count(), 1);
        $this->assertSame($band->ul_modulations->count(), 1);

        $this->assertSame('qam256', $band->dl_modulations->first()->modulation);
        $this->assertSame('qam256', $band->ul_modulations->first()->modulation);
    }
}
