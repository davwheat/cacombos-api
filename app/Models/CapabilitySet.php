<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
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

    public function combos()
    {
        return $this
            ->belongsToMany(Combo::class, 'capability_set_combo', 'capability_set_id', 'combo_id')
            ->withPivot('uuid');
    }
}
