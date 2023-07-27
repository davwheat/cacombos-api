<?php

namespace App\DataParser;

use App\Models\CapabilitySet;

interface DataParser
{
    public function __construct(array $data, CapabilitySet $capabilitySet);
    public function parseAndInsertAllModels(): void;
}
