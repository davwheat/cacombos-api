<?php

namespace Tests\Feature;

use App\Models\CapabilitySet;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ImportJsonTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected static $auth = [
        'x-auth-token' => 'admin',
    ];

    protected static $lte_ca_data = [
        'lteca' => [
            0 => [
                'components' => [
                    0 => [
                        'band' => 1,
                        'bwClassDl' => 'A',
                        'bwClassUl' => 'A',
                        'mimoDl' => [
                            'type' => 'single',
                            'value' => 4,
                        ],
                        'mimoUl' => [
                            'type' => 'single',
                            'value' => 1,
                        ],
                        'modulationDl' => [
                            'type' => 'single',
                            'value' => 'qam256',
                        ],
                        'modulationUl' => [
                            'type' => 'single',
                            'value' => 'qam64',
                        ],
                    ],
                ],
                'bcs' => [
                    'type' => 'single',
                    'value' => 0,
                ],
            ],
            1 => [
                'components' => [
                    0 => [
                        'band' => 3,
                        'bwClassDl' => 'A',
                        'bwClassUl' => 'A',
                        'mimoDl' => [
                            'type' => 'single',
                            'value' => 4,
                        ],
                        'mimoUl' => [
                            'type' => 'single',
                            'value' => 1,
                        ],
                        'modulationDl' => [
                            'type' => 'single',
                            'value' => 'qam256',
                        ],
                        'modulationUl' => [
                            'type' => 'single',
                            'value' => 'qam64',
                        ],
                    ],
                ],
                'bcs' => [
                    'type' => 'single',
                    'value' => 0,
                ],
            ],
            2 => [
                'components' => [
                    0 => [
                        'band' => 7,
                        'bwClassDl' => 'C',
                        'bwClassUl' => 'C',
                        'mimoDl' => [
                            'type' => 'mixed',
                            'value' => [2, 4],
                        ],
                        'mimoUl' => [
                            'type' => 'mixed',
                            'value' => [1, 2],
                        ],
                        'modulationDl' => [
                            'type' => 'mixed',
                            'value' => ['qam256', 'qam1024'],
                        ],
                        'modulationUl' => [
                            'type' => 'mixed',
                            'value' => ['qam64', 'qam256'],
                        ],
                    ],
                ],
                'bcs' => [
                    'type' => 'multi',
                    'value' => [1, 2, 3],
                ],
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
        $response = $this->post('/v1/actions/import-json', ['jsonData' => "test", 'deviceId' => Device::first()->id, 'capabilitySetId' => 99999999], ImportJsonTest::$auth);

        $response->assertStatus(422);
        $response->assertJson(['errors' => ['capabilitySetId' => []]]);
        $this->assertStringContainsString('capability set id is invalid', $response->getContent() ?: '');
    }

    /**
     * Cannot parse a log without a valid capability set ID.
     */
    public function test_cannot_parse_log_with_invalid_capability_set_id(): void
    {
        $response = $this->post('/v1/actions/import-json', ['jsonData' => "test", 'deviceId' => 99999999, 'capabilitySetId' => CapabilitySet::first()->id], ImportJsonTest::$auth);

        $response->assertStatus(422);
        $response->assertJson(['errors' => ['deviceId' => []]]);
        $this->assertStringContainsString('device id is invalid', $response->getContent() ?: '');
    }

    /**
     * Cannot parse a log without a valid capability set ID.
     */
    public function test_parses_lte_ca_data(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device()->first();

        $response = $this->post('/v1/actions/import-json', ['jsonData' => json_encode(ImportJsonTest::$lte_ca_data), 'deviceId' => $testingDevice->id, 'capabilitySetId' => $testingCapabilitySet->id], ImportJsonTest::$auth);

        $response->assertStatus(200);
        $this->assertEquals('null', $response->getContent());

        $testingCapabilitySet->refresh();
        $combos = $testingCapabilitySet->combos()->get();

        $this->assertEquals(3, $combos->count());
    }
}
