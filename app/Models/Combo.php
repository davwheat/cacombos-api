<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{
    public function lteComponents()
    {
        return $this
            ->belongsToMany(LteComponent::class, 'combo_components', 'combo_id', 'lte_component_id')
            ->withPivot('uuid', 'bandwidth_component_set');
    }

    public function nrComponents()
    {
        return $this
            ->belongsToMany(LteComponent::class, 'combo_components', 'combo_id', 'nr_component_id')
            ->withPivot('uuid', 'bandwidth_component_set');
    }
}
