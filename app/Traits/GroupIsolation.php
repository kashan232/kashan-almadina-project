<?php

namespace App\Traits;

use App\Scopes\GroupIsolationScope;

trait GroupIsolation
{
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new GroupIsolationScope);

        static::creating(function ($model) {
            if (auth()->check()) {
                if (!isset($model->created_by) || empty($model->created_by)) {
                    $model->created_by = auth()->id();
                }
                if (!isset($model->user_group_ids) || empty($model->user_group_ids)) {
                    $model->user_group_ids = auth()->user()->userGroups()->pluck('user_groups.id')->toArray();
                }
            }
        });
    }
}
