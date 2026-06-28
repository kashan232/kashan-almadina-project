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
        if (!$user || $user->roles->pluck('name')->contains('Admin') || $user->usertype == 'admin') {
            return;
        }

        $userId = $user->id;
        $userGroupIds = $user->userGroups()->pluck('user_groups.id')->toArray();

        $builder->where(function ($query) use ($userId, $userGroupIds) {
            // Include records created by the user
            $query->where('created_by', $userId);

            // Include records belonging to any of the user's groups
            if (!empty($userGroupIds)) {
                foreach ($userGroupIds as $groupId) {
                    $query->orWhereJsonContains('user_group_ids', (string)$groupId);
                    $query->orWhereJsonContains('user_group_ids', (int)$groupId);
                }
            }
        });
    }
}
