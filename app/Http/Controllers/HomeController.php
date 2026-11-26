<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $scopedUserId = $isAdmin ? null : $user->id;
        $fromStr = $startDate->toDateString();
        $toStr = $endDate->toDateString();

        // Base Query (actual sales only, excluding sale orders)
        $salesQuery = Sale::withoutGlobalScopes()
            ->where('is_sale_order', 0);

        // Non-admin users see ONLY their own sales
        if (!$isAdmin) {
            $salesQuery->where('created_by', $user->id);
        }

        // Filtered Actual Net Sales Amount for the active period
        $filteredSalesAmount = $this->calculateNetSales($fromStr, $toStr, $scopedUserId);

        $filteredSalesCount = (clone $salesQuery)
            ->whereBetween(DB::raw("COALESCE(NULLIF(entry_date, ''), DATE(created_at))"), [$fromStr, $toStr])
            ->count();

        // 1. User-Wise Sales Summary Cards
        // Admin sees all users who created sales; User sees only self
        $userSalesCardsQuery = Sale::withoutGlobalScopes()
            ->where('is_sale_order', 0)
            ->selectRaw('created_by, COUNT(*) as total_orders')
            ->whereBetween(DB::raw("COALESCE(NULLIF(entry_date, ''), DATE(created_at))"), [$fromStr, $toStr])
            ->groupBy('created_by');

        if (!$isAdmin) {
            $userSalesCardsQuery->where('created_by', $user->id);
        }

        $userSalesRaw = $userSalesCardsQuery->get()->keyBy('created_by');
        $userNetSales = $this->calculateNetSalesByUser($fromStr, $toStr, $scopedUserId);

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
                    'total_amount' => (float) ($userNetSales[$u->id] ?? 0),
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
                'total_amount' => (float) ($userNetSales[$user->id] ?? 0),
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
                $dStr = $dt->toDateString();
                $chartSales[] = $this->calculateNetSales($dStr, $dStr, $scopedUserId);
            }
        } elseif ($period === 'weekly') {
            // Last 8 weeks breakdown
            for ($i = 7; $i >= 0; $i--) {
                $wStart = today()->subWeeks($i)->startOfWeek();
                $wEnd = today()->subWeeks($i)->endOfWeek();
                $chartLabels[] = 'W' . $wStart->format('W') . ' (' . $wStart->format('d M') . ')';
                $chartSales[] = $this->calculateNetSales($wStart->toDateString(), $wEnd->toDateString(), $scopedUserId);
            }
        } else {
            // Monthly breakdown (Last 6 Months)
            for ($i = 5; $i >= 0; $i--) {
                $mStart = today()->subMonths($i)->startOfMonth();
                $mEnd = today()->subMonths($i)->endOfMonth();
                $chartLabels[] = $mStart->format('M Y');
                $chartSales[] = $this->calculateNetSales($mStart->toDateString(), $mEnd->toDateString(), $scopedUserId);
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

    /**
     * Calculate actual net sales (SJ - SRJ - CLM-CN + CLM-Rep) matching SalesReportController.
     */
    private function calculateNetSales(string $fromDate, string $toDate, ?int $userId = null): float
    {
        $salesSum = (float) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.is_sale_order', 0)
            ->whereBetween(DB::raw("COALESCE(NULLIF(sales.entry_date, ''), DATE(sales.created_at))"), [$fromDate, $toDate])
            ->when($userId !== null, fn ($q) => $q->where('sales.created_by', $userId))
            ->sum('sale_items.amount');

        $returnsSum = (float) DB::table('sale_return_items')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->whereBetween(DB::raw("COALESCE(NULLIF(sale_returns.entry_date, ''), NULLIF(sale_returns.`current_date`, ''), DATE(sale_returns.created_at))"), [$fromDate, $toDate])
            ->when($userId !== null, fn ($q) => $q->where('sale_returns.created_by', $userId))
            ->sum('sale_return_items.amount');

        $claimsNet = (float) DB::table('customer_claims')
            ->where('status', 'Posted')
            ->where('claim_type', 'credit_note')
            ->whereBetween(DB::raw('DATE(claim_date)'), [$fromDate, $toDate])
            ->when($userId !== null, fn ($q) => $q->where('created_by', $userId))
            ->selectRaw('COALESCE(SUM((CASE WHEN replacement_product_id IS NOT NULL OR COALESCE(replacement_sales_price, 0) > 0 THEN COALESCE(replacement_sales_price, 0) ELSE 0 END) - COALESCE(sales_price, 0)), 0) as net_claim')
            ->value('net_claim');

        return round($salesSum - $returnsSum + $claimsNet, 2);
    }

    /**
     * Calculate actual net sales grouped by created_by user ID.
     */
    private function calculateNetSalesByUser(string $fromDate, string $toDate, ?int $userId = null): array
    {
        $totals = [];

        $salesByUser = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.is_sale_order', 0)
            ->whereBetween(DB::raw("COALESCE(NULLIF(sales.entry_date, ''), DATE(sales.created_at))"), [$fromDate, $toDate])
            ->when($userId !== null, fn ($q) => $q->where('sales.created_by', $userId))
            ->groupBy('sales.created_by')
            ->selectRaw('sales.created_by, COALESCE(SUM(sale_items.amount), 0) as amt')
            ->pluck('amt', 'created_by');

        foreach ($salesByUser as $uid => $amt) {
            $totals[$uid] = ($totals[$uid] ?? 0) + (float) $amt;
        }

        $returnsByUser = DB::table('sale_return_items')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->whereBetween(DB::raw("COALESCE(NULLIF(sale_returns.entry_date, ''), NULLIF(sale_returns.`current_date`, ''), DATE(sale_returns.created_at))"), [$fromDate, $toDate])
            ->when($userId !== null, fn ($q) => $q->where('sale_returns.created_by', $userId))
            ->groupBy('sale_returns.created_by')
            ->selectRaw('sale_returns.created_by, COALESCE(SUM(sale_return_items.amount), 0) as amt')
            ->pluck('amt', 'created_by');

        foreach ($returnsByUser as $uid => $amt) {
            $totals[$uid] = ($totals[$uid] ?? 0) - (float) $amt;
        }

        $claimsByUser = DB::table('customer_claims')
            ->where('status', 'Posted')
            ->where('claim_type', 'credit_note')
            ->whereBetween(DB::raw('DATE(claim_date)'), [$fromDate, $toDate])
            ->when($userId !== null, fn ($q) => $q->where('created_by', $userId))
            ->groupBy('created_by')
            ->selectRaw('created_by, COALESCE(SUM((CASE WHEN replacement_product_id IS NOT NULL OR COALESCE(replacement_sales_price, 0) > 0 THEN COALESCE(replacement_sales_price, 0) ELSE 0 END) - COALESCE(sales_price, 0)), 0) as amt')
            ->pluck('amt', 'created_by');

        foreach ($claimsByUser as $uid => $amt) {
            $totals[$uid] = ($totals[$uid] ?? 0) + (float) $amt;
        }

        return $totals;
    }
}
