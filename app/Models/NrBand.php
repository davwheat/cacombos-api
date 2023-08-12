<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int             $id
 * @property int             $band
 * @property ?boolean        $rate_matching_lte_crs
 * @property ?string         $power_class
 * @property ?int            $max_uplink_duty_cycle
 * @property ?int            $dl_mimo_id
 * @property ?int            $ul_mimo_id
 * @property ?int            $dl_modulation_id
 * @property ?int            $ul_modulation_id
 * @property ?Mimo           $ulMimo
 * @property ?Mimo           $dlMimo
 * @property ?Modulation     $ulModulation
 * @property ?Modulation     $dlModulation
 * @property ?NrBandwidths[] $bandwidths
 */
class NrBand extends Model
{
    // Disable timestamps
    public $timestamps = false;

    public $fillable = [
        'band',
        'rate_matching_lte_crs',
        'power_class',
        'max_uplink_duty_cycle',
    ];

    public function getFrequencyRange(): FrequencyRange
    {
        if ($this->band > 256) {
            return FrequencyRange::FR2;
        } elseif ($this->band >= 255) {
            return FrequencyRange::NonTerrestrial;
        }

        return FrequencyRange::FR1;
    }

    public function ulMimo()
    {
        return $this->hasOne(Mimo::class, 'ul_mimo_id');
    }

    public function dlMimo()
    {
        return $this->hasOne(Mimo::class, 'dl_mimo_id');
    }

    public function ulModulation()
    {
        return $this->hasOne(Modulation::class, 'ul_modulation_id');
    }

    public function dlModulation()
    {
        return $this->hasOne(Modulation::class, 'dl_modulation_id');
    }

    public function bandwidths()
    {
        return $this->belongsToMany(NrBandwidths::class, 'nr_bands_nr_bandwidths', 'nr_band_id', 'bandwidth_id');
    }
}

enum FrequencyRange
{
    case FR1;
    case FR2;
    /** Unused */
    case NonTerrestrial;
}
