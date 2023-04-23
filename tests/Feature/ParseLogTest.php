<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertStringContainsString;

class ParseLogTest extends TestCase
{
    use RefreshDatabase;

    protected static $auth = [
        'x-auth-token' => "admin"
    ];

    /**
     * Cannot parse a log without a valid token.
     */
    public function test_cannot_parse_log_without_permission(): void
    {
        $response = $this->post('/v1/actions/parse-log');

        $response->assertStatus(403);
    }
    use RefreshDatabase;

    /**
     * Cannot parse a log without any data.
     */
    public function test_cannot_parse_log_without_data(): void
    {
        $response = $this->post('/v1/actions/parse-log', [], ParseLogTest::$auth);

        $response->assertStatus(422);
        $response->assertJson(['errors' => ['logFormat' => [], 'eutraLog' => [], 'eutranrLog' => [], 'nrLog' => []]]);
        $this->assertStringContainsString('field is required', $response->getContent() ?: '');
    }

    /**
     * Cannot parse a log with only partial data.
     */
    public function test_cannot_parse_log_with_partial_data(): void
    {
        $response = $this->post('/v1/actions/parse-log', [
            'logFormat' => 'qualcomm',
        ], ParseLogTest::$auth);

        $response->assertStatus(422);
        $response->assertJson(['errors' => ['eutraLog' => [], 'eutranrLog' => [], 'nrLog' => []]]);
        $this->assertStringContainsString('field is required', $response->getContent() ?: '');
    }

    /**
     * Cannot parse a log with invalid data.
     */
    // public function test_cannot_parse_log_with_invalid_data(): void
    // {
    //     $response = $this->post('/v1/actions/parse-log', [
    //         'logFormat' => 'qualcomm',
    //         'eutraLog' => file_get_contents(__DIR__ . '/../Data/Log/invalid.txt'),
    //     ], ParseLogTest::$auth);

    //     $response->assertStatus(422);
    //     $this->assertStringContainsString('Invalid log format', $response->getContent() ?: '');
    // }

    /**
     * Can parse Qualcomm EUTRA log.
     */
    public function test_can_parse_qcom_eutra_log(): void
    {
        $response = $this->post('/v1/actions/parse-log', [
            'logFormat' => 'qualcomm',
            'eutraLog' => file_get_contents(__DIR__ . '/../Data/Log/Qualcomm/b0cd.txt'),
        ], ParseLogTest::$auth);

        // $response->assertStatus(200);
        $response->assertContent('');
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertStringContainsString('DL', $response->getContent() ?: '');
    }
}
