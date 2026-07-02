<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransferProduct;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StockTransferReportController extends Controller
{
    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    private function applyDateFilter($query, ?string $from_date, ?string $to_date): void
    {
        if (!empty($from_date)) {
            $query->where(function ($sub) use ($from_date) {
                $sub->whereDate('entry_date', '>=', $from_date)
                    ->orWhere(function ($sq) use ($from_date) {
                        $sq->whereNull('entry_date')->whereDate('created_at', '>=', $from_date);
                    });
            });
        }
        if (!empty($to_date)) {
            $query->where(function ($sub) use ($to_date) {
                $sub->whereDate('entry_date', '<=', $to_date)
                    ->orWhere(function ($sq) use ($to_date) {
                        $sq->whereNull('entry_date')->whereDate('created_at', '<=', $to_date);
                    });
            });
        }
    }

    private function fromLocationLabel($transfer): string
    {
        if ($transfer->from_shop) {
            return 'Shop';
        }

        return $transfer->fromWarehouse->warehouse_name ?? 'N/A';
    }

    private function toLocationLabel($transfer): string
    {
        if ($transfer->to_shop) {
            return 'Shop';
        }

        return $transfer->toWarehouse->warehouse_name ?? 'N/A';
    }

    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $users = User::with('userGroups')->orderBy('name')->get();
        $warehouses = Warehouse::withoutGlobalScopes()->orderBy('warehouse_name')->get();
        $products = Product::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.stock_transfer.index', compact(
            'userGroups',
            'users',
            'warehouses',
            'products',
            'shopGroupIds'
        ));
    }

    public function preview(Request $request)
    {
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $grouped = $this->fetchReportLines($request)->groupBy('stock_transfer_id');

        return view('admin_panel.reports.stock_transfer.preview', compact('grouped', 'from_date', 'to_date'));
    }

    private function fetchReportLines(Request $request): Collection
    {
        $user_groups = $request->user_group ?? [];
        $officers = $request->sales_officer ?? [];
        $fromWarehouses = $request->from_warehouse ?? [];
        $toWarehouses = $request->to_warehouse ?? [];
        $items = $request->item ?? [];
        $transferId = $request->transfer_id;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $totalGroups = UserGroup::count();
        $totalUsers = User::count();
        $totalFromLocations = Warehouse::withoutGlobalScopes()->count() + 1;
        $totalToLocations = Warehouse::withoutGlobalScopes()->count() + 1;
        $totalProducts = Product::count();

        $query = StockTransferProduct::with([
            'product.brandRelation',
            'transfer.fromWarehouse',
            'transfer.toWarehouse',
        ])->whereHas('transfer', function ($q) use (
            $user_groups,
            $officers,
            $fromWarehouses,
            $toWarehouses,
            $transferId,
            $from_date,
            $to_date,
            $totalGroups,
            $totalUsers,
            $totalFromLocations,
            $totalToLocations
        ) {
            $q->withoutGlobalScopes()->whereIn('status', ['Posted', 'accepted']);
            $this->applyDateFilter($q, $from_date, $to_date);

            if (!empty($transferId)) {
                $q->where(function ($sub) use ($transferId) {
                    $sub->where('id', 'like', "%{$transferId}%")
                        ->orWhere('id', ltrim($transferId, '#'));
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

            if ($this->shouldApplyFilter($fromWarehouses, $totalFromLocations)) {
                $q->where(function ($sub) use ($fromWarehouses) {
                    foreach ($fromWarehouses as $loc) {
                        if ((string) $loc === 'shop') {
                            $sub->orWhere('from_shop', 1);
                        } else {
                            $sub->orWhere(function ($sq) use ($loc) {
                                $sq->where('from_warehouse_id', $loc)->where('from_shop', 0);
                            });
                        }
                    }
                });
            }

            if ($this->shouldApplyFilter($toWarehouses, $totalToLocations)) {
                $q->where(function ($sub) use ($toWarehouses) {
                    foreach ($toWarehouses as $loc) {
                        if ((string) $loc === 'shop') {
                            $sub->orWhere('to_shop', 1);
                        } else {
                            $sub->orWhere(function ($sq) use ($loc) {
                                $sq->where('to_warehouse_id', $loc)->where('to_shop', 0);
                            });
                        }
                    }
                });
            }
        });

        if ($this->shouldApplyFilter($items, $totalProducts)) {
            $query->whereIn('product_id', $items);
        }

        return $query->get()->sortBy(function ($line) {
            $transfer = $line->transfer;
            $date = $transfer?->entry_date ?? $transfer?->created_at;

            return sprintf(
                '%s-%s-%s',
                $date ?? '',
                str_pad((string) ($transfer?->id ?? ''), 10, '0', STR_PAD_LEFT),
                str_pad((string) $line->id, 10, '0', STR_PAD_LEFT)
            );
        })->values();
    }
}
