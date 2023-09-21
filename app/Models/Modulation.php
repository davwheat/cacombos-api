<?php

namespace App\Models;

/**
 * @property int    $id
 * @property string $modulation
 * @property bool   $is_ul
 */
class Modulation extends BaseModel
{
    // Disable timestamps
    public $timestamps = false;

    public $fillable = [
        'modulation',
        'is_ul',
    ];
}
