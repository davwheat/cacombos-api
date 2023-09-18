<?php

namespace App\DataParser\ElementParser;

class BcsParser
{
    public function getBcsFromData(array $data, string $attribute): ?array
    {
        if (empty($data[$attribute])) {
            return [];
        }

        switch ($data[$attribute]['type']) {
            case 'all':
                return ['all'];

            case 'multi':
                return $data[$attribute]['value'];

            case 'single':
                return [$data[$attribute]['value']];

            default:
            case 'empty':
                return null;
        }
    }
}
