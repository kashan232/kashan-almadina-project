<?php

namespace App\Http\Controllers;

use App\Models\ClaimAcceptanceItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ClaimAcceptanceReportController extends Controller
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

    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $users = User::with('userGroups')->orderBy('name')->get();
        $claimFromWarehouses = Warehouse::withoutGlobalScopes()
            ->where('claim_type', 'customer')
            ->orderBy('warehouse_name')
            ->get();
        $acceptInWarehouses = Warehouse::withoutGlobalScopes()
            ->where('claim_type', 'company')
            ->orderBy('warehouse_name')
            ->get();
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.claim_acceptance.index', compact(
            'userGroups',
            'users',
            'claimFromWarehouses',
            'acceptInWarehouses',
            'products',
            'customers',
            'vendors',
            'shopGroupIds'
        ));
    }

    public function preview(Request $request)
    {
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $grouped = $this->fetchReportLines($request)->groupBy('claim_acceptance_id');

        return view('admin_panel.reports.claim_acceptance.preview', compact('grouped', 'from_date', 'to_date'));
    }

    private function fetchReportLines(Request $request): Collection
    {
        $user_groups = $request->user_group ?? [];
        $officers = $request->sales_officer ?? [];
        $claimFromWarehouses = $request->claim_from_warehouse ?? [];
        $acceptInWarehouses = $request->accept_in_warehouse ?? [];
        $items = $request->item ?? [];
        $partyTypes = $request->party_type ?? [];
        $parties = $request->party ?? [];
        $voucherNo = $request->voucher_no;
        $btrNo = $request->btr_no;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $totalGroups = UserGroup::count();
        $totalUsers = User::count();
        $totalClaimFromWarehouses = Warehouse::withoutGlobalScopes()->where('claim_type', 'customer')->count();
        $totalAcceptInWarehouses = Warehouse::withoutGlobalScopes()->where('claim_type', 'company')->count();
        $totalProducts = Product::count();
        $totalPartyTypes = 3;
        $totalParties = Customer::count() + Vendor::count();

        $query = ClaimAcceptanceItem::with([
            'product',
            'voucher.vendor',
            'voucher.customer',
            'voucher.fromWarehouse',
            'voucher.toWarehouse',
        ])->whereHas('voucher', function ($q) use (
            $user_groups,
            $officers,
            $claimFromWarehouses,
            $acceptInWarehouses,
            $partyTypes,
            $parties,
            $voucherNo,
            $from_date,
            $to_date,
            $totalGroups,
            $totalUsers,
            $totalClaimFromWarehouses,
            $totalAcceptInWarehouses,
            $totalPartyTypes,
            $totalParties
        ) {
            $q->withoutGlobalScopes()->where('status', 'Posted');
            $this->applyDateFilter($q, $from_date, $to_date);

            if (!empty($voucherNo)) {
                $q->where(function ($sub) use ($voucherNo) {
                    $sub->where('voucher_no', 'like', "%{$voucherNo}%")
                        ->orWhere('voucher_no', 'like', '%' . ltrim($voucherNo, '0') . '%');
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

            if ($this->shouldApplyFilter($claimFromWarehouses, $totalClaimFromWarehouses)) {
                $q->whereIn('from_warehouse_id', $claimFromWarehouses);
            }

            if ($this->shouldApplyFilter($acceptInWarehouses, $totalAcceptInWarehouses)) {
                $q->whereIn('to_warehouse_id', $acceptInWarehouses);
            }

            if ($this->shouldApplyFilter($partyTypes, $totalPartyTypes)) {
                $q->whereIn('party_type', $partyTypes);
            }

            if ($this->shouldApplyFilter($parties, $totalParties)) {
                $q->where(function ($sub) use ($parties) {
                    foreach ($parties as $party) {
                        if (!str_contains($party, ':')) {
                            continue;
                        }
                        [$type, $id] = explode(':', $party, 2);
                        $sub->orWhere(function ($sq) use ($type, $id) {
                            $sq->where('party_type', $type)->where('party_id', $id);
                        });
                    }
                });
            }
        });

        if ($this->shouldApplyFilter($items, $totalProducts)) {
            $query->whereIn('product_id', $items);
        }

        if (!empty($btrNo)) {
            $query->where(function ($sub) use ($btrNo) {
                $sub->where('btr_no', 'like', "%{$btrNo}%")
                    ->orWhere('btr_no', 'like', '%' . ltrim($btrNo, '0') . '%');
            });
        }

        return $query->get()->sortBy(function ($item) {
            $voucher = $item->voucher;

            return sprintf(
                '%s-%s',
                $voucher?->date ?? '',
                str_pad((string) ($voucher?->voucher_no ?? ''), 10, '0', STR_PAD_LEFT)
            );
        })->values();
    }
}
