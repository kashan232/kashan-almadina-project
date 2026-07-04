<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class GroupIsolationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (!$user || $user->roles->pluck('name')->contains('Admin') || $user->id == 1) {
            return;
        }

        $userId = $user->id;
        $userGroupIds = $user->userGroups()->pluck('user_groups.id')->toArray();

        $builder->where(function ($query) use ($userId, $userGroupIds) {
            if (empty($userGroupIds)) {
                $query->where('created_by', $userId);

                return;
            }

            $query->where('created_by', $userId)
                ->orWhere(function ($sub) use ($userGroupIds) {
                    foreach ($userGroupIds as $groupId) {
                        $sub->orWhereJsonContains('user_group_ids', (string) $groupId)
                            ->orWhereJsonContains('user_group_ids', (int) $groupId);
                    }
                });
        });
    }
}
