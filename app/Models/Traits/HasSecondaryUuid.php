<?php

namespace App\Models\Traits;

use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Support\Str;

trait HasSecondaryUuid
{
    public function scopeUuid(QueryBuilder $query, string $uuid): QueryBuilder
    {
        return $query->where($this->getUuidName(), $uuid);
    }

    public function getUuidName(): string
    {
        return property_exists($this, 'uuidName') ? $this->uuidName : 'uuid';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getUuidName()} = Str::orderedUuid();
        });
    }
}
