<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int                        $id
 * @property string                     $uuid
 * @property string                     $name
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Modem extends BaseModel
{
    use Traits\HasSecondaryUuid;

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
