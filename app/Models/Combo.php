<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                        $id
 * @property string                     $combo_string
 * @property int                        $capability_set_id
 * @property ?array                     $bandwidth_combination_set_eutra
 * @property ?array                     $bandwidth_combination_set_nr
 * @property ?array                     $bandwidth_combination_set_intra_endc
 * @property CapabilitySet              $capability_set
 * @property Collection<LteComponent>   $lte_components
 * @property Collection<NrComponent>    $nr_coponents
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Combo extends BaseModel
{
    // Disable timestamps
    public $timestamps = false;

    public $fillable = [
        'combo_string',
        'bandwidth_combination_set_eutra',
        'bandwidth_combination_set_nr',
        'bandwidth_combination_set_intra_endc',
        'capability_set_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'bandwidth_combination_set_eutra'      => 'array',
        'bandwidth_combination_set_nr'         => 'array',
        'bandwidth_combination_set_intra_endc' => 'array',
    ];

    public function capability_set()
    {
        return $this->belongsTo(CapabilitySet::class);
    }

    public function lte_components()
    {
        return $this
            ->belongsToMany(LteComponent::class, 'combo_components', 'combo_id', 'lte_component_id');
    }

    public function nr_components()
    {
        return $this
            ->belongsToMany(NrComponent::class, 'combo_components', 'combo_id', 'nr_component_id');
    }
}
