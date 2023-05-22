<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mimo
 * @property boolean $is_ul
 */
class Mimo extends Model
{
    public $fillable = [
        'mimo',
        'is_ul',
    ];
}
