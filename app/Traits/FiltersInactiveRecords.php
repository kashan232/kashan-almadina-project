<?php

namespace App\Traits;

use App\Models\Scopes\ActiveOnlyScope;
use Illuminate\Database\Eloquent\Builder;

trait FiltersInactiveRecords
{
    public static function bootFiltersInactiveRecords(): void
    {
        static::addGlobalScope(new ActiveOnlyScope());
    }

    public static function withInactive(): Builder
    {
        return static::withoutGlobalScope(ActiveOnlyScope::class);
    }

    public function scopeWithInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope(ActiveOnlyScope::class);
    }
}

