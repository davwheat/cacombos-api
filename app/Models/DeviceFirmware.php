<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property int $device_id
 * @property string $name
 * @property ?string $plmn
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
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
