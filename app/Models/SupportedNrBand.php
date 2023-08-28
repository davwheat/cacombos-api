<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                    $id
 * @property int                    $band
 * @property int|null               $max_uplink_duty_cycle
 * @property string|null            $power_class
 * @property boolean|null           $rate_matching_lte_crs
 * @property ?array                  $bandwidths
 * @property boolean|null           $supports_endc
 * @property boolean|null           $supports_sa
 * @property CapabilitySet          $capabilitySet
 * @property Collection<Modulation> $modulations
 * @property Collection<Modulation> $dl_modulations
 * @property Collection<Modulation> $ul_modulations
 * @property Collection<Mimo>       $mimos
 * @property Collection<Mimo>       $dl_mimos
 * @property Collection<Mimo>       $ul_mimos
 */
class SupportedNrBand extends Model
{
    // Disable timestamps
    public $timestamps = false;

    public $fillable = [
        'band',
        'max_uplink_duty_cycle',
        'power_class',
        'rate_matching_lte_crs',
        'bandwidths',
        'supports_endc',
        'supports_sa',
        'capability_set_id',
    ];

    protected $casts = [
        'bandwidths' => 'array'
    ];

    public function capabilitySet()
    {
        return $this->belongsTo(CapabilitySet::class);
    }

    public function modulations()
    {
        return $this->belongsToMany(Modulation::class, 'supported_nr_bands_modulations');
    }

    public function mimos()
    {
        return $this->belongsToMany(Mimo::class, 'supported_nr_bands_mimos');
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
