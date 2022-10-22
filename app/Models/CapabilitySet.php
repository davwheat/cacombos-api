<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapabilitySet extends Model
{
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function deviceFirmware()
    {
        return $this->belongsTo(DeviceFirmware::class);
    }
}
