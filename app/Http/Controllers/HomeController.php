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
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $isAdmin = $user->isAdmin();
        $period = $request->get('period', 'monthly'); // daily, weekly, monthly

        // Date range scoping based on selected filter
        $now = now();
        if ($period === 'daily') {
            $startDate = $now->copy()->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($period === 'weekly') {
            $startDate = $now->copy()->startOfWeek();
            $endDate = $now->copy()->endOfWeek();
        } else { // monthly (default)
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        }

        // Base Query
        $salesQuery = Sale::withoutGlobalScopes();

        // Non-admin users see ONLY their own sales
        if (!$isAdmin) {
            $salesQuery->where('created_by', $user->id);
        }

        // Filtered Total Sales Amount for the active period
        $filteredSalesAmount = (clone $salesQuery)
            ->whereBetween('entry_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('total_balance');

        $filteredSalesCount = (clone $salesQuery)
            ->whereBetween('entry_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        // 1. User-Wise Sales Summary Cards
        // Admin sees all users who created sales; User sees only self
        $userSalesCardsQuery = Sale::withoutGlobalScopes()
            ->selectRaw('created_by, COUNT(*) as total_orders, SUM(total_balance) as total_amount')
            ->whereBetween('entry_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('created_by');

        if (!$isAdmin) {
            $userSalesCardsQuery->where('created_by', $user->id);
        }

        $userSalesRaw = $userSalesCardsQuery->get()->keyBy('created_by');

        // Map with User names
        $userCards = [];
        if ($isAdmin) {
            $usersList = \App\Models\User::all();
            foreach ($usersList as $u) {
                $stat = $userSalesRaw->get($u->id);
                $userCards[] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'total_orders' => (int) ($stat->total_orders ?? 0),
                    'total_amount' => (float) ($stat->total_amount ?? 0),
                ];
            }
            // Sort by total_amount descending
            usort($userCards, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);
        } else {
            $stat = $userSalesRaw->get($user->id);
            $userCards[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_orders' => (int) ($stat->total_orders ?? 0),
                'total_amount' => (float) ($stat->total_amount ?? 0),
            ];
        }

        // 2. Chart Data Generation according to period
        $chartLabels = [];
        $chartSales = [];

        if ($period === 'daily') {
            // Last 7 days daily breakdown
            for ($i = 6; $i >= 0; $i--) {
                $dt = today()->subDays($i);
                $chartLabels[] = $dt->format('d M (D)');
                $amt = (clone $salesQuery)
                    ->whereDate('entry_date', $dt->toDateString())
                    ->sum('total_balance');
                $chartSales[] = (float) $amt;
            }
        } elseif ($period === 'weekly') {
            // Last 8 weeks breakdown
            for ($i = 7; $i >= 0; $i--) {
                $wStart = today()->subWeeks($i)->startOfWeek();
                $wEnd = today()->subWeeks($i)->endOfWeek();
                $chartLabels[] = 'W' . $wStart->format('W') . ' (' . $wStart->format('d M') . ')';
                $amt = (clone $salesQuery)
                    ->whereBetween('entry_date', [$wStart->toDateString(), $wEnd->toDateString()])
                    ->sum('total_balance');
                $chartSales[] = (float) $amt;
            }
        } else {
            // Monthly breakdown (Last 6 Months)
            for ($i = 5; $i >= 0; $i--) {
                $mStart = today()->subMonths($i)->startOfMonth();
                $mEnd = today()->subMonths($i)->endOfMonth();
                $chartLabels[] = $mStart->format('M Y');
                $amt = (clone $salesQuery)
                    ->whereBetween('entry_date', [$mStart->toDateString(), $mEnd->toDateString()])
                    ->sum('total_balance');
                $chartSales[] = (float) $amt;
            }
        }

        $chartData = [
            'labels' => $chartLabels,
            'sales'  => $chartSales,
        ];

        return view('admin_panel.dashboard', compact(
            'isAdmin',
            'period',
            'filteredSalesAmount',
            'filteredSalesCount',
            'userCards',
            'chartData'
        ));
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
