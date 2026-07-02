<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerClaim;
use App\Models\Product;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class ClaimReportController extends Controller
{
    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    private function applyClaimDateFilter($query, ?string $from_date, ?string $to_date): void
    {
        if (!empty($from_date)) {
            $query->whereDate('claim_date', '>=', $from_date);
        }
        if (!empty($to_date)) {
            $query->whereDate('claim_date', '<=', $to_date);
        }
    }

    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $users = User::with('userGroups')->orderBy('name')->get();
        $claimWarehouses = Warehouse::withoutGlobalScopes()
            ->where('claim_type', 'customer')
            ->orderBy('warehouse_name')
            ->get();
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.claim.index', compact(
            'userGroups', 'users', 'claimWarehouses', 'products', 'customers', 'vendors', 'shopGroupIds'
        ));
    }

    public function preview(Request $request)
    {
        $user_groups = $request->user_group ?? [];
        $officers = $request->sales_officer ?? [];
        $claimWarehouses = $request->claim_warehouse ?? [];
        $claimTypes = $request->claim_type ?? [];
        $items = $request->item ?? [];
        $partyTypes = $request->party_type ?? [];
        $parties = $request->party ?? [];
        $claimNo = $request->claim_no;
        $claimEntryType = $request->input('claim_entry_type', 'all');
        $reportType = $request->report_type;
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $totalGroups = UserGroup::count();
        $totalUsers = User::count();
        $totalClaimWarehouses = Warehouse::withoutGlobalScopes()->where('claim_type', 'customer')->count();
        $totalClaimTypes = 3;
        $totalProducts = Product::count();
        $totalPartyTypes = 3;
        $totalParties = Customer::count() + Vendor::count();

        $query = CustomerClaim::with([
            'product.brandRelation',
            'replacementProduct',
            'warehouse',
            'party',
            'creator',
        ])->where('status', 'Posted');

        $this->applyClaimDateFilter($query, $from_date, $to_date);

        if (!empty($claimNo)) {
            $query->where(function ($sub) use ($claimNo) {
                $sub->where('claim_no', 'like', "%{$claimNo}%")
                    ->orWhere('claim_no', 'like', '%' . ltrim($claimNo, '0') . '%');
            });
        }

        if ($this->shouldApplyFilter($officers, $totalUsers)) {
            $query->whereIn('created_by', $officers);
        }

        if ($this->shouldApplyFilter($user_groups, $totalGroups)) {
            $query->where(function ($sub) use ($user_groups) {
                foreach ($user_groups as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }

        if ($this->shouldApplyFilter($claimWarehouses, $totalClaimWarehouses)) {
            $query->whereIn('claim_warehouse_id', $claimWarehouses);
        }

        if ($this->shouldApplyFilter($claimTypes, $totalClaimTypes)) {
            $query->whereIn('claim_type', $claimTypes);
        }

        if ($claimEntryType !== 'all') {
            $query->where('claim_type', $claimEntryType);
        }

        if ($this->shouldApplyFilter($items, $totalProducts)) {
            $query->whereIn('product_id', $items);
        }

        if ($this->shouldApplyFilter($partyTypes, $totalPartyTypes)) {
            $query->whereIn('party_type', $partyTypes);
        }

        if ($this->shouldApplyFilter($parties, $totalParties)) {
            $query->where(function ($sub) use ($parties) {
                foreach ($parties as $partyKey) {
                    if (str_contains($partyKey, ':')) {
                        [$type, $id] = explode(':', $partyKey, 2);
                        $sub->orWhere(function ($q) use ($type, $id) {
                            $q->where('party_type', $type)->where('party_id', $id);
                        });
                    } else {
                        $sub->orWhere('party_id', $partyKey);
                    }
                }
            });
        }

        $claims = $query->orderBy('claim_date')->orderBy('id')->get();

        if ($reportType === 'Party Wise') {
            return $this->previewPartyWise($claims, $from_date, $to_date);
        }
        if ($reportType === 'Claim Type Wise') {
            return $this->previewClaimTypeWise($claims, $from_date, $to_date);
        }
        if ($reportType === 'Brand Wise') {
            return $this->previewBrandWise($claims, $from_date, $to_date);
        }
        if ($reportType === 'Item Wise') {
            return $this->previewItemWise($claims, $from_date, $to_date);
        }
        if ($reportType === 'Claim Wise') {
            return $this->previewClaimWise($claims, $from_date, $to_date);
        }

        return back()->with('error', 'Invalid Report Type Selected');
    }

    private function partyKey(CustomerClaim $claim): string
    {
        return $claim->party_type . ':' . $claim->party_id;
    }

    private function previewPartyWise($claims, $from_date, $to_date)
    {
        $grouped = $claims->groupBy(fn ($claim) => $this->partyKey($claim));

        return view('admin_panel.reports.claim.preview_party', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewClaimTypeWise($claims, $from_date, $to_date)
    {
        $order = ['item_return', 'credit_note', 'claim_hold'];
        $grouped = $claims->groupBy('claim_type')->sortBy(function ($items, $type) use ($order) {
            $index = array_search($type, $order, true);
            return $index === false ? 99 : $index;
        });

        return view('admin_panel.reports.claim.preview_claim_type', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewBrandWise($claims, $from_date, $to_date)
    {
        $sorted = $claims->sortBy(fn ($claim) => [
            $claim->product?->brandRelation?->name ?? 'ZZZ',
            $claim->product?->name ?? '',
            $claim->claim_date,
            $claim->id,
        ]);

        $grouped = $sorted
            ->groupBy(fn ($claim) => $claim->product?->brandRelation?->name ?? 'N/A')
            ->map(fn ($brandClaims) => $brandClaims->groupBy('product_id'));

        return view('admin_panel.reports.claim.preview_brand', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewItemWise($claims, $from_date, $to_date)
    {
        $grouped = $claims->groupBy('product_id');

        return view('admin_panel.reports.claim.preview_item', compact('grouped', 'from_date', 'to_date'));
    }

    private function previewClaimWise($claims, $from_date, $to_date)
    {
        $grouped = $claims->groupBy('claim_no');

        return view('admin_panel.reports.claim.preview_claim', compact('grouped', 'from_date', 'to_date'));
    }
}
