<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubcategoryController extends Controller
{

    public function index()
    {
        $category = Category::get();
        $subcategory = Subcategory::with('category')->get();
        return  view("admin_panel.subcategory.index", compact('subcategory', 'category'));
    }

    public function store(Request $request)
    {
        // validation rules
        $rules = [
            'name' => [
                'required',
                // ignore current record when editing
                Rule::unique('subcategories', 'name')->ignore($request->edit_id)
            ],
            'category_id' => 'required', // <-- fixed: no concatenation
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            // return JSON with 422 so frontend can handle it
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Use filled() to check if edit_id contains a non-empty value
        if ($request->filled('edit_id')) {
            $company = Subcategory::find($request->edit_id);
            $msg = [
                'success' => 'Subcategory Updated Successfully',
                'reload' => true
            ];
        } else {
            $company = new Subcategory();
            $msg = [
                'success' => 'Subcategory Created Successfully',
                'redirect' => route('subcategory.home')
            ];
        }

        $company->name = $request->name;
        $company->category_id = $request->category_id;
        $company->save();

        return response()->json($msg);
    }


    public function delete($id)
    {

        $company = Subcategory::find($id);
        if ($company) {
            $company->delete();
            $msg = [
                'success' => 'Subcategory Deleted Successfully',
                'reload' =>  route('subcategory.home'),
            ];
        } else {
            $msg = ['error' => 'Subcategory Not Found'];
        }
        return response()->json($msg);
    }
}
