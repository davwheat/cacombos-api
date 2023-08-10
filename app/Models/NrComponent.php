<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int                        $id
 * @property int                        $band
 * @property ?string                    $dl_class
 * @property ?string                    $ul_class
 * @property ?int                       $bandwidth
 * @property ?bool                      $supports_90mhz_bw
 * @property ?int                       $subcarrier_spacing
 * @property int                        $component_index
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class NrComponent extends Model
{
    // Disable timestamps
    public $timestamps = false;

    public $fillable = [
        'band',
        'dl_class',
        'ul_class',
        'bandwidth',
        'supports_90mhz_bw',
        'subcarrier_spacing',
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

    public function dl_mimos()
    {
        return $this->mimos()->where('is_ul', false);
    }

    public function ul_mimos()
    {
        return $this->mimos()->where('is_ul', true);
    }

    public function dl_modulations()
    {
        return $this->modulations()->where('is_ul', false);
    }

    public function ul_modulations()
    {
        return $this->modulations()->where('is_ul', true);
    }
}
