<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property int $band
 * @property ?string $dl_class
 * @property ?string $ul_class
 * @property ?int $bandwidth
 * @property ?int $subcarrier_spacing
 * @property ?int $dl_mimo
 * @property ?int $ul_mimo
 * @property ?string $dl_modulation
 * @property ?string $ul_modulation
 */
class NrComponent extends Model
{
}
