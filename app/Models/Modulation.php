<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $modulation
 * @property bool   $is_ul
 */
class Modulation extends Model
{
    // Disable timestamps
    public $timestamps = false;
    
    public $fillable = [
        'modulation',
        'is_ul',
    ];
}
