<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Customer;
use App\Models\CustomerClaim;
use App\Models\Product;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $user = auth()->user();
        $isAdmin = !$user || $user->roles->pluck('name')->contains('Admin') || $user->id == 1;

        $userGroups = UserGroup::orderBy('group_name')
            ->when(!$isAdmin, function ($q) use ($user) {
                $groupIds = $user ? $user->userGroups()->pluck('user_groups.id')->toArray() : [];
                $q->whereIn('id', $groupIds);
            })
            ->get();
        $users = User::with(['userGroups', 'roles'])->orderBy('name')->get();
        $claimWarehouses = Warehouse::withoutGlobalScopes()
            ->where('claim_type', 'customer')
            ->orderBy('warehouse_name')
            ->get();
        $brands = Brand::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.claim.index', compact(
            'userGroups', 'users', 'claimWarehouses', 'brands', 'products', 'customers', 'vendors', 'shopGroupIds'
        ));
    }

    public function preview(Request $request)
    {
        $payload = $this->resolveReportPayload($request);
        if (!$payload) {
            return back()->with('error', 'Invalid Report Type Selected');
        }

        return view($payload['view'], $payload['data']);
    }

    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $payload = $this->resolveReportPayload($request);
        if (!$payload) {
            return back()->with('error', 'Invalid Report Type Selected');
        }

        $pdf = Pdf::loadView($payload['pdfView'], $payload['data'])
            ->setPaper('a4', $payload['orientation'])
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->get_font('Helvetica', 'normal');
        $canvas->page_text(
            $canvas->get_width() - 65,
            $canvas->get_height() - 16,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            7.5,
            [0.2, 0.2, 0.2]
        );

        return $pdf->download($payload['filename']);
    }

    private function resolveReportPayload(Request $request): ?array
    {
        $user_groups = $request->user_group ?? [];
        $officers = $request->sales_officer ?? [];
        $claimWarehouses = $request->claim_warehouse ?? [];
        $claimTypes = $request->claim_type ?? [];
        $brands = $request->brand ?? [];
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
        $totalBrands = Brand::count();
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

        if ($this->shouldApplyFilter($brands, $totalBrands)) {
            $query->whereHas('product', function ($q) use ($brands) {
                $q->whereIn('brand_id', $brands);
            });
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
            return $this->partyWisePayload($claims, $from_date, $to_date);
        }
        if ($reportType === 'Claim Type Wise') {
            return $this->claimTypeWisePayload($claims, $from_date, $to_date);
        }
        if ($reportType === 'Brand Wise') {
            return $this->brandWisePayload($claims, $from_date, $to_date);
        }
        if ($reportType === 'Item Wise') {
            return $this->itemWisePayload($claims, $from_date, $to_date);
        }
        if ($reportType === 'Claim Wise') {
            return $this->claimWisePayload($claims, $from_date, $to_date);
        }

        return null;
    }

    private function partyKey(CustomerClaim $claim): string
    {
        return $claim->party_type . ':' . $claim->party_id;
    }

    private function partyWisePayload($claims, $from_date, $to_date)
    {
        $grouped = $claims->groupBy(fn ($claim) => $this->partyKey($claim));

        return [
            'view' => 'admin_panel.reports.claim.preview_party',
            'pdfView' => 'admin_panel.reports.claim.pdf_party',
            'orientation' => 'landscape',
            'filename' => "Claim_Report_Party_Wise_{$from_date}_to_{$to_date}.pdf",
            'data' => compact('grouped', 'from_date', 'to_date'),
        ];
    }

    private function claimTypeWisePayload($claims, $from_date, $to_date)
    {
        $order = ['item_return', 'credit_note', 'claim_hold'];
        $grouped = $claims->groupBy('claim_type')->sortBy(function ($items, $type) use ($order) {
            $index = array_search($type, $order, true);
            return $index === false ? 99 : $index;
        });

        return [
            'view' => 'admin_panel.reports.claim.preview_claim_type',
            'pdfView' => 'admin_panel.reports.claim.pdf_claim_type',
            'orientation' => 'landscape',
            'filename' => "Claim_Report_Claim_Type_Wise_{$from_date}_to_{$to_date}.pdf",
            'data' => compact('grouped', 'from_date', 'to_date'),
        ];
    }

    private function brandWisePayload($claims, $from_date, $to_date)
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

        return [
            'view' => 'admin_panel.reports.claim.preview_brand',
            'pdfView' => 'admin_panel.reports.claim.pdf_brand',
            'orientation' => 'portrait',
            'filename' => "Claim_Report_Brand_Wise_{$from_date}_to_{$to_date}.pdf",
            'data' => compact('grouped', 'from_date', 'to_date'),
        ];
    }

    private function itemWisePayload($claims, $from_date, $to_date)
    {
        $grouped = $claims->groupBy('product_id');

        return [
            'view' => 'admin_panel.reports.claim.preview_item',
            'pdfView' => 'admin_panel.reports.claim.pdf_item',
            'orientation' => 'landscape',
            'filename' => "Claim_Report_Item_Wise_{$from_date}_to_{$to_date}.pdf",
            'data' => compact('grouped', 'from_date', 'to_date'),
        ];
    }

    private function claimWisePayload($claims, $from_date, $to_date)
    {
        $grouped = $claims->groupBy('claim_no');

        return [
            'view' => 'admin_panel.reports.claim.preview_claim',
            'pdfView' => 'admin_panel.reports.claim.pdf_claim',
            'orientation' => 'landscape',
            'filename' => "Claim_Report_Claim_Wise_{$from_date}_to_{$to_date}.pdf",
            'data' => compact('grouped', 'from_date', 'to_date'),
        ];
    }
}
