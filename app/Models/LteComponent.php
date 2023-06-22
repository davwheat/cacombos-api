<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int                        $id
 * @property int                        $band
 * @property ?string                    $dl_class
 * @property ?string                    $ul_class
 * @property int                        $component_index
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class LteComponent extends Model
{
    public $fillable = [
        'band',
        'dl_class',
        'ul_class',
        'component_index',
    ];

    public function modulations()
    {
        return $this->belongsToMany(Modulation::class, 'components_modulations');
    }

    public function mimos()
    {
        return $this->belongsToMany(Mimo::class, 'components_mimos');
    }
}
