<?php

namespace App\Models;

/**
 * @property int     $id
 * @property int     $band
 * @property ?string $dl_class
 * @property ?string $ul_class
 * @property ?int    $bandwidth
 * @property ?bool   $supports_90mhz_bw
 * @property ?int    $subcarrier_spacing
 * @property int     $component_index
 */
class NrComponent extends BaseModel
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

    protected $casts = [
        'supports_90mhz_bw' => 'boolean',
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
