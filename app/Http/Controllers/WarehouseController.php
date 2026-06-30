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
        $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin');
        $query = Warehouse::with(['creator']);
        
        // Admin can filter by user
        if ($isAdmin && $request->has('created_by') && $request->created_by != '') {
            $query->where('created_by', $request->created_by);
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
