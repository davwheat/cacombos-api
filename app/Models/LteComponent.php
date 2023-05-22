<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $band
 * @property ?string $dl_class
 * @property ?string $ul_class
 * @property ?int $mimo
 * @property ?string $dl_modulation
 * @property ?string $ul_modulation
 * @property int $component_index
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class LteComponent extends Model
{
    public $fillable = [
        'band',
        'dl_class',
        'ul_class',
        'mimo',
        'dl_modulation',
        'ul_modulation',
        'component_index',
    ];
}
