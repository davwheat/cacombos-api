<?php

namespace Tests\Feature;

use App\Models\CapabilitySet;
use App\Models\Device;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParseImportLogTest extends TestCase
{
    use RefreshDatabase;
    use ArraySubsetAsserts;

    protected $seed = true;

    protected static $auth = [
        'x-auth-token' => 'admin',
    ];

    private function remove_metadata_from_output(array $output): array
    {
        return array_map(function ($item) {
            unset($item['metadata']);
            unset($item['timestamp']);
            unset($item['parserVersion']);

            return $item;
        }, $output);
    }

    /**
     * Cannot parse a log without a valid token.
     */
    public function test_cannot_parse_and_import_log_without_permission(): void
    {
        $response = $this->post('/v1/actions/parse-import-log');

        $response->assertStatus(403);
    }

    /**
     * Cannot parse a log without any data.
     */
    public function test_cannot_parse_and_import_log_without_data(): void
    {
        $response = $this->post('/v1/actions/parse-import-log', [], static::$auth);

        $response->assertStatus(422);
        $response->assertJson(['errors' => ['logFormat' => [], 'eutraLog' => [], 'eutranrLog' => [], 'nrLog' => [], 'deviceId' => [], 'capabilitySetId' => []]]);
        $this->assertStringContainsString('field is required', $response->getContent() ?: '');
    }

    /**
     * Cannot parse a log with only partial data.
     */
    public function test_cannot_parse_and_import_log_with_partial_data(): void
    {
        $response = $this->post('/v1/actions/parse-import-log', [
            'logFormat' => 'qualcomm',
        ], static::$auth);

        $response->assertStatus(422);
        $response->assertJson(['errors' => ['eutraLog' => [], 'eutranrLog' => [], 'deviceId' => [], 'capabilitySetId' => []]]);
        $this->assertStringContainsString('field is required', $response->getContent() ?: '');
    }

    /**
     * Cannot parse a log with invalid data.
     */
    public function test_cannot_parse_and_import_log_with_invalid_data(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/parse-import-log', [
            'logFormat'       => 'qualcomm',
            'eutraLog'        => file_get_contents(__DIR__.'/../Data/Log/invalid.txt'),
            'capabilitySetId' => $testingCapabilitySet->id,
            'deviceId'        => $testingDevice->id,

        ], static::$auth);

        $response->assertStatus(422);
        $this->assertStringContainsString('Parser failed to find any capability data in one or more of the provided log files', $response->getContent() ?: '');
    }

    /**
     * Cannot parse a log with nonsensical format name.
     */
    public function test_cannot_parse_and_import_log_with_unsupported_format(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/parse-import-log', [
            'logFormat'       => 'not-a-format',
            'eutraLog'        => file_get_contents(__DIR__.'/../Data/Log/invalid.txt'),
            'capabilitySetId' => $testingCapabilitySet->id,
            'deviceId'        => $testingDevice->id,
        ], static::$auth);

        $response->assertStatus(422);
        $this->assertStringContainsString('The selected log format is invalid.', $response->getContent() ?: '');
    }

    /**
     * Can parse Qualcomm EUTRA log.
     */
    public function test_can_parse_qcom_eutra_log(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/parse-import-log', [
            'logFormat'       => 'qualcomm',
            'eutraLog'        => file_get_contents(__DIR__.'/../Data/Log/Qualcomm/b0cd.txt'),
            'capabilitySetId' => $testingCapabilitySet->id,
            'deviceId'        => $testingDevice->id,
        ], static::$auth);

        $actual_json = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $this->assertEquals($actual_json, null);
    }

    /**
     * Can parse Qualcomm EUTRA-NR log.
     */
    public function test_can_parse_qcom_eutranr_log(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/parse-import-log', [
            'logFormat'       => 'qualcomm',
            'eutranrLog'      => file_get_contents(__DIR__.'/../Data/Log/Qualcomm/b826.txt'),
            'capabilitySetId' => $testingCapabilitySet->id,
            'deviceId'        => $testingDevice->id,
        ], static::$auth);

        $actual_json = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $this->assertEquals($actual_json, null);
    }

    /**
     * Can parse and merge Qualcomm EUTRA and NR log.
     */
    public function test_can_parse_and_merge_qcom_eutra_and_eutranr_log(): void
    {
        /** @var CapabilitySet */
        $testingCapabilitySet = CapabilitySet::first();
        /** @var Device */
        $testingDevice = $testingCapabilitySet->device;

        $response = $this->post('/v1/actions/parse-import-log', [
            'logFormat'       => 'qualcomm',
            'eutraLog'        => file_get_contents(__DIR__.'/../Data/Log/Qualcomm/b0cd.txt'),
            'eutranrLog'      => file_get_contents(__DIR__.'/../Data/Log/Qualcomm/b826.txt'),
            'capabilitySetId' => $testingCapabilitySet->id,
            'deviceId'        => $testingDevice->id,
        ], static::$auth);

        $actual_json = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $this->assertEquals($actual_json, null);
    }
}
