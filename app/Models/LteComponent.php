<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property int $band
 * @property ?string $dl_class
 * @property ?string $ul_class
 * @property ?int $mimo
 * @property ?string $dl_modulation
 * @property ?string $ul_modulation
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class LteComponent extends Model
{
}
