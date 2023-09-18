<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                    $id
 * @property int                    $band
 * @property string|null            $power_class
 * @property CapabilitySet          $capabilitySet
 * @property Collection<Modulation> $modulations
 * @property Collection<Modulation> $dlModulations
 * @property Collection<Modulation> $ulModulations
 * @property Collection<Mimo>       $mimos
 * @property Collection<Mimo>       $dlMimos
 * @property Collection<Mimo>       $ulMimos
 */
class SupportedLteBand extends Model
{
    // Disable timestamps
    public $timestamps = false;

    public $fillable = [
        'band',
        'power_class',
        'capability_set_id',
    ];

    public function capabilitySet()
    {
        return $this->belongsTo(CapabilitySet::class);
    }

    public function modulations()
    {
        return $this->belongsToMany(Modulation::class, 'supported_lte_bands_modulations');
    }

    public function mimos()
    {
        return $this->belongsToMany(Mimo::class, 'supported_lte_bands_mimos');
    }

    public function dlMimos()
    {
        return $this->mimos()->where('is_ul', false);
    }

    public function ulMimos()
    {
        return $this->mimos()->where('is_ul', true);
    }

    public function dlModulations()
    {
        return $this->modulations()->where('is_ul', false);
    }

    public function ulModulations()
    {
        return $this->modulations()->where('is_ul', true);
    }
}
