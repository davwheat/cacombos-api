<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;
use Psr\Http\Message\UploadedFileInterface;

class FileOrString implements InvokableRule
{
    private int $maxSize;

    public function __construct(int $maxSize = -1)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * @param string   $attribute
     * @param mixed    $value
     * @param \Closure $fail
     *
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if (!is_string($value) && !($value instanceof UploadedFileInterface)) {
            $fail('The :attribute must either be a string or file.');
        }

        if ($value instanceof UploadedFileInterface) {
            /**
             * @var UploadedFileInterface $value
             */
            if ($this->maxSize > 0 && $value->getSize() > $this->maxSize) {
                $fail('The :attribute must be less than '.$this->maxSize.' bytes.');
            }
        }
    }
}
