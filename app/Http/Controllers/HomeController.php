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
                // Dashboard Statistics
                $stats = [
                    // Products
                    'total_products' => Product::count(),
                    
                    // Inward Gatepass
                    'total_inward' => Inwardgatepass::count(),
                    'inward_with_bills' => Inwardgatepass::where('status', 'linked')->count(), // Fixed: use status='linked'
                    'inward_pending_bills' => Inwardgatepass::where('status', 'pending')->count(), // Fixed: use status='pending'
                    
                    // Purchases
                    'total_purchases' => Purchase::count(),
                    'total_purchase_amount' => Purchase::sum('net_amount') ?? 0, // Fixed: use net_amount
                    
                    
                    // Vendors
                    'total_vendors' => Vendor::count(),
                    
                    
                    // Sales
                    'total_sales' => Sale::count(),
                    'total_sales_amount' => Sale::sum('total_balance') ?? 0,
                    'today_sales' => Sale::whereDate('created_at', today())->count(),
                    'today_sales_amount' => Sale::whereDate('created_at', today())->sum('total_balance') ?? 0,
                    
                    // Stock Holds
                    'total_stock_holds' => StockHold::where('status', '0')->count(), // Fixed: status=0 means pending
                    // Note: Removed stock_hold_value sum - column name uncertain
                    
                    
                    
                    // Customers
                    'total_customers' => Customer::count(),
                    
                    // Customer Credit
                    'total_customer_credit' => Sale::sum('previous_balance') ?? 0,
                    'pending_payments' => Sale::where('total_balance', '>', 0)->sum('total_balance') ?? 0,
                ];
                
                // Chart Data - Sales & Purchases
                
                // Daily Sales (Last 7 days)
                $dailySales = [];
                $dailySalesLabels = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = today()->subDays($i);
                    $dailySalesLabels[] = $date->format('D');
                    $dailySales[] = Sale::whereDate('created_at', $date)->sum('total_balance') ?? 0;
                }
                
                // Daily Purchases (Last 7 days)
                $dailyPurchases = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = today()->subDays($i);
                    $dailyPurchases[] = Purchase::whereDate('created_at', $date)->sum('net_amount') ?? 0;
                }
                
                // Weekly Sales (Last 6 weeks)
                $weeklySales = [];
                $weeklySalesLabels = [];
                for ($i = 5; $i >= 0; $i--) {
                    $weekStart = today()->subWeeks($i)->startOfWeek();
                    $weekEnd = today()->subWeeks($i)->endOfWeek();
                    $weeklySalesLabels[] = 'Week ' . ($i == 0 ? 'Current' : $weekStart->format('d M'));
                    $weeklySales[] = Sale::whereBetween('created_at', [$weekStart, $weekEnd])->sum('total_balance') ?? 0;
                }
                
                // Weekly Purchases (Last 6 weeks)
                $weeklyPurchases = [];
                for ($i = 5; $i >= 0; $i--) {
                    $weekStart = today()->subWeeks($i)->startOfWeek();
                    $weekEnd = today()->subWeeks($i)->endOfWeek();
                    $weeklyPurchases[] = Purchase::whereBetween('created_at', [$weekStart, $weekEnd])->sum('net_amount') ?? 0;
                }
                
                // Monthly Sales (Last 6 months)
                $monthlySales = [];
                $monthlySalesLabels = [];
                for ($i = 5; $i >= 0; $i--) {
                    $month = today()->subMonths($i);
                    $monthlySalesLabels[] = $month->format('M Y');
                    $monthlySales[] = Sale::whereYear('created_at', $month->year)
                                         ->whereMonth('created_at', $month->month)
                                         ->sum('total_balance') ?? 0;
                }
                
                // Monthly Purchases (Last 6 months)
                $monthlyPurchases = [];
                for ($i = 5; $i >= 0; $i--) {
                    $month = today()->subMonths($i);
                    $monthlyPurchases[] = Purchase::whereYear('created_at', $month->year)
                                                   ->whereMonth('created_at', $month->month)
                                                   ->sum('net_amount') ?? 0;
                }
                
                $chartData = [
                    'daily' => [
                        'labels' => $dailySalesLabels,
                        'sales' => $dailySales,
                        'purchases' => $dailyPurchases,
                    ],
                    'weekly' => [
                        'labels' => $weeklySalesLabels,
                        'sales' => $weeklySales,
                        'purchases' => $weeklyPurchases,
                    ],
                    'monthly' => [
                        'labels' => $monthlySalesLabels,
                        'sales' => $monthlySales,
                        'purchases' => $monthlyPurchases,
                    ],
                ];
                
                // Recent activities
                $recent_sales = Sale::with('customer')->latest()->take(5)->get();
                $recent_purchases = Purchase::with('vendor')->latest()->take(5)->get();
                $stock_holds_details = StockHold::where('status', '0')->latest()->take(10)->get(); // status=0 means pending
                
                
                return view('admin_panel.dashboard', compact('userId', 'stats', 'chartData', 'recent_sales', 'recent_purchases', 'stock_holds_details'));
            }  

            else
            {
                return redirect()->back(); 
            }
         }
    }
}
