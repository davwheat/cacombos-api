<?php

namespace App\Validator;

use Illuminate\Contracts\Validation\InvokableRule;
use Psr\Http\Message\UploadedFileInterface;

class FileOrString implements InvokableRule
{

    public function __invoke(string $attribute, mixed $value, \Closure $fail)
    {
        if (!is_string($value) && !($value instanceof UploadedFileInterface)) {
            $fail('The :attribute must either be a string or file.');
        }
    }
}
