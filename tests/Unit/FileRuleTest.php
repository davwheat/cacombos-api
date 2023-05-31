<?php

namespace Tests\Unit;

use App\Rules\File;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\UploadedFileInterface;
use Tests\UnitTestCase;

class FileRuleTest extends UnitTestCase
{
    private function is_valid_file_or_string(mixed $value, bool $required = true)
    {
        $validator = Validator::make(
            [
                'file' => $value,
            ],
            [
                'file' => [$required ? 'required' : '', new File()],
            ]
        );

        return $validator->passes();
    }

    /**
     * An instance of UploadedFileInterface is a valid file.
     *
     * @return void
     */
    public function test_that_file_is_valid_file()
    {
        $mockFile = $this->createMock(UploadedFileInterface::class);

        $this->assertTrue($this->is_valid_file_or_string($mockFile));
    }

    /**
     * A string is not a valid file.
     *
     * @return void
     */
    public function test_that_string_is_not_valid_file()
    {
        $this->assertFalse($this->is_valid_file_or_string('abc'));
    }

    /**
     * An empty array is not a valid file.
     *
     * @return void
     */
    public function test_that_empty_array_is_not_valid_file()
    {
        $this->assertFalse($this->is_valid_file_or_string([]));
    }

    /**
     * An empty object is not a valid file.
     *
     * @return void
     */
    public function test_that_empty_object_is_not_valid_file()
    {
        $this->assertFalse($this->is_valid_file_or_string(new \stdClass()));
    }

    /**
     * An array of files is not a valid file.
     *
     * @return void
     */
    public function test_that_array_of_files_is_not_valid_file()
    {
        $mockFile = $this->createMock(UploadedFileInterface::class);

        $this->assertFalse($this->is_valid_file_or_string([$mockFile]));
    }

    public function test_that_no_input_is_valid_if_not_required()
    {
        $this->assertTrue($this->is_valid_file_or_string('', false));
    }
}
