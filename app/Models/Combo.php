<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $combo_string
 * @property int $capability_set_id
 * @property array $bandwidth_combination_set
 * @property CapabilitySet $capabilitySet
 * @property Collection<LteComponent> $lteComponents
 * @property Collection<NrComponent> $nrComponents
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Combo extends Model
{
    use Traits\HasSecondaryUuid;

    public $fillable = [
        'combo_string',
        'bandwidth_combination_set',
        'capability_set_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'bandwidth_combination_set' => 'array',
    ];

    public function capabilitySet()
    {
        return $this->belongsTo(CapabilitySet::class);
    }

    public function lteComponents()
    {
        return $this
            ->belongsToMany(LteComponent::class, 'combo_components', 'combo_id', 'lte_component_id');
    }

    public function nrComponents()
    {
        return $this
            ->belongsToMany(LteComponent::class, 'combo_components', 'combo_id', 'nr_component_id');
    }
}
