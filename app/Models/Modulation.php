<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $modulation
 * @property boolean $is_ul
 */
class Modulation extends Model
{
    public $fillable = [
        'modulation',
        'is_ul',
    ];
}
