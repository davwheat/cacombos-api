<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                        $id
 * @property int                        $band
 * @property string|null                $power_class
 * @property CapabilitySet              $capabilitySet
 * @property Collection<Modulation>     $modulations
 * @property Collection<Modulation>     $dl_modulations
 * @property Collection<Modulation>     $ul_modulations
 * @property Collection<Mimo>           $mimos
 * @property Collection<Mimo>           $dl_mimos
 * @property Collection<Mimo>           $ul_mimos
 */
class SupportedLteBand extends Model
{
    // Disable timestamps
    public $timestamps = false;

    public $fillable = [
        'band',
        'power_class',
    ];

    public function capabilitySet()
    {
        return $this->belongsTo(CapabilitySet::class);
    }

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
