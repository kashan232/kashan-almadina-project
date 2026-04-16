<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserGroup;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::with(['creator']);
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::user()->usertype == 'admin';
        // Check if user is NOT an admin
        if (!$isAdmin) {
            $userId = Auth::id();
            $userGroupIds = Auth::user()->userGroups()->pluck('user_groups.id')->toArray();
            
            $query->where(function($q) use ($userId, $userGroupIds) {
                // created by user
                $q->where('created_by', $userId);
                
                // OR belongs to user's group
                if (!empty($userGroupIds)) {
                    foreach ($userGroupIds as $groupId) {
                        $q->orWhereJsonContains('user_group_ids', (string)$groupId);
                    }
                }
            });
        } else {
            // Admin can filter by user
            if ($request->has('created_by') && $request->created_by != '') {
                $query->where('created_by', $request->created_by);
            }
        }

        $warehouses = $query->latest()->get();
        $users = User::all();
        $userGroups = UserGroup::all();
        $branches = Branch::all();

        return view('admin_panel.warehouses.index', compact('warehouses', 'users', 'userGroups', 'branches', 'isAdmin'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_name' => 'required',
            'branch_id' => 'nullable',
            'user_group_ids' => 'nullable|array',
        ]);

        $data = $request->all();
        
        if ($request->id) {
            Warehouse::findOrFail($request->id)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            Warehouse::create($data);
        }
        return back()->with('success', 'Saved Successfully');
    }

    public function delete($id)
    {
        Warehouse::findOrFail($id)->delete();
        return back()->with('success', 'Deleted Successfully');
    }
}
