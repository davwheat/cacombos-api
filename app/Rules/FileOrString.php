<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;
use Psr\Http\Message\UploadedFileInterface;

class FileOrString implements InvokableRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param \Closure $fail
     * @return void
     */
    public function __invoke($attribute,  $value, $fail)
    {
        if (!is_string($value) && !($value instanceof UploadedFileInterface)) {
            $fail('The :attribute must either be a string or file.');
        }
    }
}
