<?php

namespace App\Traits;

use App\Scopes\GroupIsolationScope;
use App\Support\GroupContext;

trait GroupIsolation
{
    protected static function booted()
    {
        static::addGlobalScope(new GroupIsolationScope);

        static::creating(function ($model) {
            if (!auth()->check()) {
                return;
            }

            if (!isset($model->created_by) || empty($model->created_by)) {
                $model->created_by = auth()->id();
            }

            GroupContext::applyToModel($model);
        });

        static::updating(function ($model) {
            if (!auth()->check() || GroupContext::shouldSkipAutoResolve($model)) {
                return;
            }

            if (GroupContext::hasPartyFieldChanges($model)) {
                GroupContext::applyToModel($model);
            }
        });
    }
}
