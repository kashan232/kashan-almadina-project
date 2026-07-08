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

    /**
     * Warehouses visible to the logged-in user (admin = all, user = group-assigned).
     */
    public static function accessibleQuery()
    {
        $user = auth()->user();

        if ($user && $user->isAdmin()) {
            return static::withoutGlobalScopes();
        }

        return static::withoutGlobalScope('exclude_claims');
    }

    public static function accessibleByClaimType(string $claimType, ?int $includeWarehouseId = null)
    {
        $warehouses = static::accessibleQuery()
            ->where('claim_type', $claimType)
            ->orderBy('warehouse_name')
            ->get();

        if ($includeWarehouseId && !$warehouses->contains('id', $includeWarehouseId)) {
            $extra = static::withoutGlobalScopes()->find($includeWarehouseId);
            if ($extra && $extra->claim_type === $claimType) {
                $warehouses->push($extra);
            }
        }

        return $warehouses->sortBy('warehouse_name')->values();
    }

    public static function isAccessibleToUser(int $warehouseId, ?string $claimType = null): bool
    {
        $query = static::accessibleQuery()->where('id', $warehouseId);

        if ($claimType !== null) {
            $query->where('claim_type', $claimType);
        }

        return $query->exists();
    }

    /** All warehouses for cross-location transfers (no group or claim-type filter). */
    public static function allForSelection()
    {
        return static::withoutGlobalScopes()->orderBy('warehouse_name')->get();
    }

}
