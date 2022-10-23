<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

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
