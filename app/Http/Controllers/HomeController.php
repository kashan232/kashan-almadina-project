<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\InwardGatepass;
use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\Sale;
use App\Models\StockHold;
use App\Models\Customer;

class HomeController extends Controller
{
    public function index()
    {
         if(Auth::id())
         {
            $usertype =Auth()->user()->usertype;
            $userId = Auth::id();
            if($usertype=='user')
            {
                return view('user_panel.dashboard', [
                    'userId' => $userId,
                ]);
            } 
             
            else if($usertype=='admin')
            {
                return view('admin_panel.dashboard', compact('userId'));
            }  

            else
            {
                return redirect()->back(); 
            }
         }
         return redirect()->route('login');
    }

    public function dashboardReport()
    {
        $userId = Auth::id();
        // Dashboard Statistics
        $stats = [
            // Products
            'total_products' => Product::count(),
            
            // Inward Gatepass
            'total_inward' => InwardGatepass::count(),
            'inward_with_bills' => InwardGatepass::where('status', 'linked')->count(), 
            'inward_pending_bills' => InwardGatepass::where('status', 'pending')->count(), 
            
            // Purchases
            'total_purchases' => Purchase::count(),
            'total_purchase_amount' => Purchase::sum('net_amount') ?? 0, 
            
            // Vendors
            'total_vendors' => Vendor::count(),
            
            // Sales
            'total_sales' => Sale::count(),
            'total_sales_amount' => Sale::sum('total_balance') ?? 0,
            'today_sales' => Sale::whereDate('created_at', today())->count(),
            'today_sales_amount' => Sale::whereDate('created_at', today())->sum('total_balance') ?? 0,
            
            // Stock Holds
            'total_stock_holds' => \App\Models\StockHold::where('status', '0')->count(), 
            
            // Customers
            'total_customers' => Customer::count(),
            
            // Customer Credit
            'total_customer_credit' => Sale::sum('previous_balance') ?? 0,
            'pending_payments' => Sale::where('total_balance', '>', 0)->sum('total_balance') ?? 0,
        ];
        
        // Chart Data - Sales & Purchases
        $dailySales = []; $dailySalesLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dailySalesLabels[] = $date->format('D');
            $dailySales[] = Sale::whereDate('created_at', $date)->sum('total_balance') ?? 0;
        }
        $dailyPurchases = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dailyPurchases[] = Purchase::whereDate('created_at', $date)->sum('net_amount') ?? 0;
        }
        
        $chartData = [
            'daily' => [
                'labels' => $dailySalesLabels,
                'sales' => $dailySales,
                'purchases' => $dailyPurchases,
            ],
        ];
        
        $recent_sales = Sale::with('customer')->latest()->take(5)->get();
        $recent_purchases = Purchase::with('vendor')->latest()->take(5)->get();
        $stock_holds_details = \App\Models\StockHold::where('status', '0')->latest()->take(10)->get(); 
        
        return view('admin_panel.reports.dashboard', compact('userId', 'stats', 'chartData', 'recent_sales', 'recent_purchases', 'stock_holds_details'));
    }
}
