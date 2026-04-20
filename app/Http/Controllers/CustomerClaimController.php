<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerClaimController extends Controller
{
    public function index()
    {
        return view('admin_panel.customer_claims.index');
    }

    public function create()
    {
        $products = Product::where('status', 1)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        
        // Safe generation if table doesn't exist yet
        $claimNo = 'CLM-1';
        if (\Schema::hasTable('customer_claims')) {
            $claimNo = 'CLM-' . (DB::table('customer_claims')->count() + 1);
        }
        
        return view('admin_panel.customer_claims.create', compact('products', 'warehouses', 'claimNo'));
    }
}
