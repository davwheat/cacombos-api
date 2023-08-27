<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                          $id
 * @property string                       $uuid
 * @property string                       $description
 * @property ?string                      $plmn
 * @property ?int                         $lte_category_dl
 * @property ?int                         $lte_category_ul
 * @property DeviceFirmware               $deviceFirmware
 * @property Collection<Combo>            $combos
 * @property Collection<SupportedLteBand> $supportedLteBands
 * @property \Illuminate\Support\Carbon   $created_at
 * @property \Illuminate\Support\Carbon   $updated_at
 */
class CapabilitySet extends Model
{
    use Traits\HasSecondaryUuid;

    public function device(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->deviceFirmware()->first()->device();
    }

    public function deviceFirmware(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeviceFirmware::class);
    }

    public function combos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Combo::class);
    }

    public function supportedLteBands(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupportedLteBand::class);
    }
}
