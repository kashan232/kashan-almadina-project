<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserGroup;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user_groups = UserGroup::with('users')->get();
        $users = User::where('email', '!=', 'admin@admin.com')->get();
        return view('admin_panel.user_group.user_group', compact('user_groups', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $editId = $request->edit_id ?? null;
        $validator = Validator::make($request->all(), [
            'group_name' => 'required|unique:user_groups,group_name,' . $editId,
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {
            // Save or update logic
            if (!empty($editId)) {
                $userGroup = UserGroup::findOrFail($editId);
                $msg = [
                    'success' => 'User Group Updated Successfully',
                    'reload' => true
                ];
            } else {
                $userGroup = new UserGroup();
                $msg = [
                    'success' => 'User Group Created Successfully',
                    'redirect' => route('user-group.index')
                ];
            }

            $userGroup->group_name = $request->group_name;
            $userGroup->save();

            // Sync user assignments
            $userGroup->users()->sync($request->user_ids);

            DB::commit();
            return response()->json($msg);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
