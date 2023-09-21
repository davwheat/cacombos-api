<?php

namespace App\Models;

/**
 * @property int                        $id
 * @property string                     $uuid
 * @property string                     $device_name
 * @property string                     $model_name
 * @property string                     $manufacturer
 * @property int                        $modem_id
 * @property \Illuminate\Support\Carbon $release_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Device extends BaseModel
{
    use Traits\HasSecondaryUuid;

    /**
     * @var array
     */
    protected $casts = [
        'release_date' => 'datetime',
    ];

    public function device_firmwares()
    {
        return $this->hasMany(DeviceFirmware::class);
    }

    public function capability_sets()
    {
        return $this->hasManyThrough(CapabilitySet::class, DeviceFirmware::class);
    }

    public function modem()
    {
        return $this->belongsTo(Modem::class);
    }
}
