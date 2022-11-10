<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $band
 * @property ?string $dl_class
 * @property ?string $ul_class
 * @property ?int $bandwidth
 * @property ?int $subcarrier_spacing
 * @property ?int $dl_mimo
 * @property ?int $ul_mimo
 * @property ?string $dl_modulation
 * @property ?string $ul_modulation
 */
class NrComponent extends Model
{
    public $fillable = [
        'band',
        'dl_class',
        'ul_class',
        'bandwidth',
        'subcarrier_spacing',
        'dl_mimo',
        'ul_mimo',
        'dl_modulation',
        'ul_modulation',
    ];
}
