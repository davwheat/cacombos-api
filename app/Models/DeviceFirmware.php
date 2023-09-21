<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;

/**
 * @property int                        $id
 * @property string                     $uuid
 * @property int                        $device_id
 * @property string                     $name
 * @property Device                     $device
 * @property Collection<CapabilitySet>  $capabilitySets
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DeviceFirmware extends BaseModel
{
    use Traits\HasSecondaryUuid;

    protected $table = 'device_firmwares';

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function capability_sets()
    {
        return $this->hasMany(CapabilitySet::class);
    }
}
