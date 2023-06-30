<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int   $id
 * @property int   $scs
 * @property array $bandwidths_dl
 * @property array $bandwidths_ul
 */
class NrBandwidths extends Model
{
    // Disable timestamps
    public $timestamps = false;

    protected $casts = [
        'bandwidths_dl' => 'array',
        'bandwidths_ul' => 'array',
    ];

    public $fillable = [
        'scs',
        'bandwidths_dl',
        'bandwidths_ul',
    ];
}
