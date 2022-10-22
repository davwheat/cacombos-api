<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class DeviceFirmware extends Model
{
    

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function capabilitySets()
    {
        return $this->hasMany(CapabilitySet::class);
    }
}
