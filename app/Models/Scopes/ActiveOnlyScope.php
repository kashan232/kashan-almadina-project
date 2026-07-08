<?php

namespace App\Models\Scopes;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveOnlyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        if ($model instanceof Product) {
            $builder->where($table . '.status', 1);

            return;
        }

        if ($model instanceof Customer || $model instanceof Vendor) {
            $builder->where($table . '.status', 'active');

            return;
        }

        if ($model instanceof Account || $model instanceof AccountHead) {
            $builder->where($table . '.status', 1);
        }
    }
}
