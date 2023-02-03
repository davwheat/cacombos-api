<?php

namespace App\Validator;

use Psr\Http\Message\UploadedFileInterface;

class FileOrStringValidator
{

    public function __invoke($attribute, $value, $fail)
    {
        if (!is_string($value) && !($value instanceof UploadedFileInterface)) {
            $fail('The ' . $attribute . ' must either be a string or file.');
        }
    }
}
