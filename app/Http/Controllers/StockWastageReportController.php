<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Product;
use App\Models\StockWastageDetail;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StockWastageReportController extends Controller
{
    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    private function applyDateFilter($query, ?string $from_date, ?string $to_date, string $column = 'date'): void
    {
        if (!empty($from_date)) {
            $query->whereDate($column, '>=', $from_date);
        }
        if (!empty($to_date)) {
            $query->whereDate($column, '<=', $to_date);
        }
    }

    private function warehouseLabel($warehouseId, $warehouseRelation): string
    {
        if ($warehouseId === 0 || $warehouseId === '0' || $warehouseId === null) {
            return 'Shop Stock';
        }

        return $warehouseRelation->warehouse_name ?? 'N/A';
    }

    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $users = User::with('userGroups')->orderBy('name')->get();
        $warehouses = Warehouse::withoutGlobalScopes()->orderBy('warehouse_name')->get();
        $products = Product::orderBy('name')->get();
        $accountHeads = AccountHead::where('status', 1)->orderBy('name')->get();
        $accounts = Account::orderBy('title')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.stock_wastage.index', compact(
            'userGroups',
            'users',
            'warehouses',
            'products',
            'accountHeads',
            'accounts',
            'shopGroupIds'
        ));
    }

    public function preview(Request $request)
    {
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $lines = $this->fetchReportLines($request);

        return view('admin_panel.reports.stock_wastage.preview', compact('lines', 'from_date', 'to_date'));
    }

    private function fetchReportLines(Request $request): Collection
    {
        $user_groups = $request->user_group ?? [];
        $officers = $request->sales_officer ?? [];
        $warehouses = $request->warehouse ?? [];
        $items = $request->item ?? [];
        $accountHeads = $request->account_head ?? [];
        $accounts = $request->account ?? [];
        $gwnId = $request->gwn_id;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $totalGroups = UserGroup::count();
        $totalUsers = User::count();
        $totalWarehouses = Warehouse::withoutGlobalScopes()->count() + 1;
        $totalProducts = Product::count();
        $totalAccountHeads = AccountHead::count();
        $totalAccounts = Account::count();

        $query = StockWastageDetail::with([
            'product',
            'wastage.warehouse',
            'wastage.account',
            'wastage.accountHead',
        ])->whereHas('wastage', function ($q) use (
            $user_groups,
            $officers,
            $warehouses,
            $accountHeads,
            $accounts,
            $gwnId,
            $from_date,
            $to_date,
            $totalGroups,
            $totalUsers,
            $totalWarehouses,
            $totalAccountHeads,
            $totalAccounts
        ) {
            $q->withoutGlobalScopes()->where('status', 'Posted');
            $this->applyDateFilter($q, $from_date, $to_date);

            if (!empty($gwnId)) {
                $q->where(function ($sub) use ($gwnId) {
                    $sub->where('gwn_id', 'like', "%{$gwnId}%")
                        ->orWhere('gwn_id', 'like', '%' . ltrim($gwnId, '0') . '%');
                });
            }

            if ($this->shouldApplyFilter($officers, $totalUsers)) {
                $q->whereIn('created_by', $officers);
            }

            if ($this->shouldApplyFilter($user_groups, $totalGroups)) {
                $q->where(function ($sub) use ($user_groups) {
                    foreach ($user_groups as $gid) {
                        $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                            ->orWhereJsonContains('user_group_ids', (int) $gid);
                    }
                });
            }

            if ($this->shouldApplyFilter($warehouses, $totalWarehouses)) {
                $q->where(function ($sub) use ($warehouses) {
                    $sub->whereIn('warehouse_id', $warehouses);
                    if (in_array('0', array_map('strval', $warehouses), true)) {
                        $sub->orWhereNull('warehouse_id')->orWhere('warehouse_id', 0);
                    }
                });
            }

            if ($this->shouldApplyFilter($accountHeads, $totalAccountHeads)) {
                $q->whereIn('account_head_id', $accountHeads);
            }

            if ($this->shouldApplyFilter($accounts, $totalAccounts)) {
                $q->whereIn('account_id', $accounts);
            }
        });

        if ($this->shouldApplyFilter($items, $totalProducts)) {
            $query->whereIn('product_id', $items);
        }

        return $query->get()->sortBy(function ($line) {
            $wastage = $line->wastage;

            return sprintf(
                '%s-%s-%s',
                $wastage?->date ?? '',
                str_pad((string) ($wastage?->gwn_id ?? ''), 10, '0', STR_PAD_LEFT),
                str_pad((string) $line->id, 10, '0', STR_PAD_LEFT)
            );
        })->values();
    }
}
