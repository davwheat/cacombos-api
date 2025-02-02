<?php

namespace App\Models;

/**
 * @property int                        $id
 * @property string                     $token
 * @property string                     $comment
 * @property string                     $type
 * @property \Illuminate\Support\Carbon $expires_after
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Token extends BaseModel
{
    /**
     * @var array
     */
    protected $casts = [
        'expires_after' => 'datetime',
    ];
}
