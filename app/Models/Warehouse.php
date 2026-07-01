<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory, \App\Traits\GroupIsolation;

    protected $guarded = [];

    protected $casts = [
        'user_group_ids' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(){
        return $this->belongsTo(User::class, 'creater_id');
    }

    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\GroupIsolationScope);

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

        static::addGlobalScope('exclude_claims', function ($builder) {
            $builder->where(function ($query) {
                $query->where('claim_type', 'none')->orWhereNull('claim_type');
            });
        });
    }

}
