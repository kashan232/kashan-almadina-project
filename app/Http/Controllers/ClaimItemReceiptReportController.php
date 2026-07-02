<?php

namespace App\Http\Controllers;

use App\Models\ClaimCreditNoteItem;
use App\Models\ClaimItemReceiptItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ClaimItemReceiptReportController extends Controller
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

    private function lineFormAmounts(float $price, float $discPct, float $retail, float $qty, float $lineTotal): array
    {
        $base = $retail > 0 ? $retail : $price;
        $unitDiscAmt = $base * $discPct / 100;
        $formRate = $price - $unitDiscAmt;
        $formLineTotal = $lineTotal != 0.0 ? $lineTotal : ($formRate * $qty);

        return [$formRate, $formLineTotal];
    }

    private function warehouseLabel($warehouseId, $warehouseRelation): string
    {
        if ($warehouseId === 0 || $warehouseId === '0') {
            return 'Shop Stock';
        }

        return $warehouseRelation->warehouse_name ?? 'N/A';
    }

    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $users = User::with('userGroups')->orderBy('name')->get();
        $deductFromWarehouses = Warehouse::withoutGlobalScopes()
            ->where('claim_type', 'company')
            ->orderBy('warehouse_name')
            ->get();
        $addToWarehouses = Warehouse::withoutGlobalScopes()
            ->orderBy('warehouse_name')
            ->get();
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.claim_item_receipt.index', compact(
            'userGroups',
            'users',
            'deductFromWarehouses',
            'addToWarehouses',
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
        $transaction_type = $request->input('transaction_type', 'all');
        $grouped = $this->buildReportLines($request)->groupBy('group_key');

        return view('admin_panel.reports.claim_item_receipt.preview', compact(
            'grouped',
            'from_date',
            'to_date',
            'transaction_type'
        ));
    }

    private function buildReportLines(Request $request): Collection
    {
        $transactionType = $request->input('transaction_type', 'all');
        $lines = collect();

        if (in_array($transactionType, ['item_receipt', 'all'], true)) {
            $lines = $lines->merge(
                $this->fetchReceiptLines($request)->map(fn ($item) => $this->wrapReceiptLine($item))
            );
        }

        if (in_array($transactionType, ['credit_note', 'all'], true)) {
            $lines = $lines->merge(
                $this->fetchCreditNoteLines($request)->map(fn ($item) => $this->wrapCreditNoteLine($item))
            );
        }

        return $lines->sortBy(fn ($line) => sprintf(
            '%s-%s-%s',
            $line->sort_date ?? '',
            $line->entry_type ?? '',
            str_pad((string) ($line->voucher_no ?? ''), 10, '0', STR_PAD_LEFT)
        ))->values();
    }

    private function extractFilters(Request $request): array
    {
        return [
            'user_groups' => $request->user_group ?? [],
            'officers' => $request->sales_officer ?? [],
            'from_warehouses' => $request->from_warehouse ?? [],
            'to_warehouses' => $request->to_warehouse ?? [],
            'items' => $request->item ?? [],
            'party_types' => $request->party_type ?? [],
            'parties' => $request->party ?? [],
            'voucher_no' => $request->voucher_no,
            'btr_no' => $request->btr_no,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'totalGroups' => UserGroup::count(),
            'totalUsers' => User::count(),
            'totalFromWarehouses' => Warehouse::withoutGlobalScopes()->where('claim_type', 'company')->count() + 1,
            'totalToWarehouses' => Warehouse::withoutGlobalScopes()->count() + 1,
            'totalProducts' => Product::count(),
            'totalPartyTypes' => 3,
            'totalParties' => Customer::count() + Vendor::count(),
        ];
    }

    private function applyHeaderFilters($query, array $filters): void
    {
        $query->withoutGlobalScopes()->where('status', 'Posted');
        $this->applyDateFilter($query, $filters['from_date'], $filters['to_date']);

        if (!empty($filters['voucher_no'])) {
            $voucherNo = $filters['voucher_no'];
            $query->where(function ($sub) use ($voucherNo) {
                $sub->where('voucher_no', 'like', "%{$voucherNo}%")
                    ->orWhere('voucher_no', 'like', '%' . ltrim($voucherNo, '0') . '%');
            });
        }

        if ($this->shouldApplyFilter($filters['officers'], $filters['totalUsers'])) {
            $query->whereIn('created_by', $filters['officers']);
        }

        if ($this->shouldApplyFilter($filters['user_groups'], $filters['totalGroups'])) {
            $query->where(function ($sub) use ($filters) {
                foreach ($filters['user_groups'] as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }

        if ($this->shouldApplyFilter($filters['from_warehouses'], $filters['totalFromWarehouses'])) {
            $query->whereIn('from_warehouse_id', $filters['from_warehouses']);
        }

        if ($this->shouldApplyFilter($filters['to_warehouses'], $filters['totalToWarehouses'])) {
            $query->where(function ($sub) use ($filters) {
                $sub->whereIn('to_warehouse_id', $filters['to_warehouses']);
                if (in_array('0', array_map('strval', $filters['to_warehouses']), true)) {
                    $sub->orWhere('to_warehouse_id', 0);
                }
            });
        }

        if ($this->shouldApplyFilter($filters['party_types'], $filters['totalPartyTypes'])) {
            $normalized = collect($filters['party_types'])->flatMap(function ($type) {
                return $type === 'walkin' ? ['walkin', 'walking'] : [$type];
            })->unique()->values()->all();

            $query->whereIn('party_type', $normalized);
        }

        if ($this->shouldApplyFilter($filters['parties'], $filters['totalParties'])) {
            $query->where(function ($sub) use ($filters) {
                foreach ($filters['parties'] as $party) {
                    if (!str_contains($party, ':')) {
                        continue;
                    }
                    [$type, $id] = explode(':', $party, 2);
                    $sub->orWhere(function ($sq) use ($type, $id) {
                        $sq->where('party_id', $id)->where(function ($pt) use ($type) {
                            if ($type === 'walkin') {
                                $pt->whereIn('party_type', ['walkin', 'walking']);
                            } else {
                                $pt->where('party_type', $type);
                            }
                        });
                    });
                }
            });
        }
    }

    private function applyProductFilters($query, array $filters): void
    {
        if ($this->shouldApplyFilter($filters['items'], $filters['totalProducts'])) {
            $query->whereIn('product_id', $filters['items']);
        }

        if (!empty($filters['btr_no'])) {
            $btrNo = $filters['btr_no'];
            $query->where(function ($sub) use ($btrNo) {
                $sub->where('btr_no', 'like', "%{$btrNo}%")
                    ->orWhere('btr_no', 'like', '%' . ltrim($btrNo, '0') . '%');
            });
        }
    }

    private function fetchReceiptLines(Request $request): Collection
    {
        $filters = $this->extractFilters($request);

        $query = ClaimItemReceiptItem::with([
            'product.latestPrice',
            'receipt.vendor',
            'receipt.customer',
            'receipt.fromWarehouse',
            'receipt.toWarehouse',
        ])->whereHas('receipt', function ($q) use ($filters) {
            $this->applyHeaderFilters($q, $filters);
        });

        $this->applyProductFilters($query, $filters);

        return $query->get();
    }

    private function fetchCreditNoteLines(Request $request): Collection
    {
        $filters = $this->extractFilters($request);

        $query = ClaimCreditNoteItem::with([
            'product.latestPrice',
            'creditNote.vendor',
            'creditNote.customer',
            'creditNote.fromWarehouse',
            'creditNote.toWarehouse',
        ])->whereHas('creditNote', function ($q) use ($filters) {
            $this->applyHeaderFilters($q, $filters);
        });

        $this->applyProductFilters($query, $filters);

        return $query->get();
    }

    private function wrapReceiptLine(ClaimItemReceiptItem $item): object
    {
        $receipt = $item->receipt;
        $qty = (float) $item->quantity;

        return (object) [
            'group_key' => 'receipt_' . $receipt->id,
            'entry_type' => 'item_receipt',
            'entry_type_label' => 'Item Receipt',
            'voucher_id' => $receipt->id,
            'voucher_no' => $receipt->voucher_no,
            'sort_date' => $receipt->date,
            'date' => $receipt->date,
            'from_warehouse_name' => $this->warehouseLabel($receipt->from_warehouse_id, $receipt->fromWarehouse),
            'to_warehouse_name' => $this->warehouseLabel($receipt->to_warehouse_id, $receipt->toWarehouse),
            'party_name' => $receipt->partyName(),
            'btr_no' => $item->btr_no,
            'product' => $item->product,
            'quantity' => $qty,
            'form_rate' => null,
            'retail_price' => null,
            'retail_value' => null,
            'form_line_total' => null,
        ];
    }

    private function wrapCreditNoteLine(ClaimCreditNoteItem $item): object
    {
        $note = $item->creditNote;
        $qty = (float) $item->quantity;
        [$formRate, $formLineTotal] = $this->lineFormAmounts(
            (float) $item->price,
            (float) ($item->discount_percent ?? 0),
            (float) ($item->retail_price ?? 0),
            $qty,
            (float) ($item->line_total ?? 0)
        );
        $retailPrice = (float) ($item->retail_price ?? 0);
        if ($retailPrice <= 0 && $item->product?->latestPrice) {
            $retailPrice = (float) $item->product->latestPrice->sale_retail_price;
        }

        return (object) [
            'group_key' => 'credit_' . $note->id,
            'entry_type' => 'credit_note',
            'entry_type_label' => 'Credit Note',
            'voucher_id' => $note->id,
            'voucher_no' => $note->voucher_no,
            'sort_date' => $note->date,
            'date' => $note->date,
            'from_warehouse_name' => $this->warehouseLabel($note->from_warehouse_id, $note->fromWarehouse),
            'to_warehouse_name' => $this->warehouseLabel($note->to_warehouse_id, $note->toWarehouse),
            'party_name' => $note->partyName(),
            'btr_no' => $item->btr_no,
            'product' => $item->product,
            'quantity' => $qty,
            'form_rate' => $formRate,
            'retail_price' => $retailPrice,
            'retail_value' => $retailPrice * $qty,
            'form_line_total' => $formLineTotal,
        ];
    }
}
