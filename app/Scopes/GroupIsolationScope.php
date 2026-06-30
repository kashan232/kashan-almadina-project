<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class GroupIsolationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();

        // If not logged in, or if user is an Admin, skip the scope
        if (!$user || $user->roles->pluck('name')->contains('Admin')) {
            return;
        }

        $userId = $user->id;
        $userGroupIds = $user->userGroups()->pluck('user_groups.id')->toArray();

        $builder->where(function ($query) use ($userId, $userGroupIds) {
            if (empty($userGroupIds)) {
                // If the user has no groups assigned, they can only see what they created
                $query->where('created_by', $userId);
            } else {
                // If the user has groups, they must strictly only see records assigned to those groups
                // (Even if they created the record, if it was moved to another group, they lose access)
                $query->where(function($sub) use ($userGroupIds) {
                    foreach ($userGroupIds as $groupId) {
                        $sub->orWhereJsonContains('user_group_ids', (string)$groupId);
                        $sub->orWhereJsonContains('user_group_ids', (int)$groupId);
                    }
                });
            }
        });
    }
}
