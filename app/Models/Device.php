<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $device_name
 * @property string $model_name
 * @property string $manufacturer
 * @property int $modem_id
 * @property \Illuminate\Support\Carbon $release_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Device extends Model
{
    /**
     * @var array
     */
    protected $casts = [
        'release_date' => 'datetime',
    ];

    public function deviceFirmwares()
    {
        return $this->hasMany(DeviceFirmware::class);
    }

    public function capabilitySets()
    {
        return $this->hasMany(CapabilitySet::class);
    }

    public function modem()
    {
        return $this->belongsTo(Modem::class);
    }
}
