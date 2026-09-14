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

        if ($transaction_type === 'btr_wise') {
            return $this->buildBtrWiseReport($request);
        }

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
            'do_no' => $request->do_no,
            'do_date' => $request->do_date,
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

        if (!empty($filters['do_no'])) {
            $query->where('do_no', 'like', '%' . trim((string) $filters['do_no']) . '%');
        }

        if (!empty($filters['do_date'])) {
            $query->whereDate('do_date', $filters['do_date']);
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
            'do_no' => $receipt->do_no,
            'do_date' => $receipt->do_date,
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
            'do_no' => $note->do_no,
            'do_date' => $note->do_date,
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

    private function buildBtrWiseReport(Request $request)
    {
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $selectedUserGroups = array_map('intval', (array) $request->input('user_group', []));
        $totalUserGroups = UserGroup::count();
        $applyGroupFilter = $this->shouldApplyFilter($selectedUserGroups, $totalUserGroups);

        $selectedUsers = array_map('intval', (array) $request->input('sales_officer', []));
        $totalUsers = User::count();
        $applyUserFilter = $this->shouldApplyFilter($selectedUsers, $totalUsers);

        $selectedDeductFrom = (array) $request->input('from_warehouse', []);
        $totalDeductFrom = Warehouse::withoutGlobalScopes()->where('claim_type', 'company')->count();
        $applyDeductFromFilter = $this->shouldApplyFilter($selectedDeductFrom, $totalDeductFrom);

        $selectedAddTo = (array) $request->input('to_warehouse', []);
        $totalAddTo = Warehouse::withoutGlobalScopes()->count() + 1; // + Shop Stock (0)
        $applyAddToFilter = $this->shouldApplyFilter($selectedAddTo, $totalAddTo);

        $selectedItems = array_map('intval', (array) $request->input('item', []));
        $totalItems = Product::count();
        $applyItemFilter = $this->shouldApplyFilter($selectedItems, $totalItems);

        $selectedCustomers = array_map('intval', (array) $request->input('customer', []));
        $totalCustomers = Customer::count();
        $applyCustomerFilter = $this->shouldApplyFilter($selectedCustomers, $totalCustomers);

        $selectedVendors = array_map('intval', (array) $request->input('vendor', []));
        $totalVendors = Vendor::count();
        $applyVendorFilter = $this->shouldApplyFilter($selectedVendors, $totalVendors);

        $btrFilter = trim((string) $request->input('btr_no', ''));
        $voucherNoFilter = trim((string) $request->input('voucher_no', ''));

        // Query ClaimAcceptanceItem
        $acceptanceItemsQuery = \App\Models\ClaimAcceptanceItem::with(['voucher.fromWarehouse', 'voucher.toWarehouse', 'voucher.vendor', 'voucher.customer', 'product.brandRelation'])
            ->whereHas('voucher', function ($q) use (
                $from_date, $to_date,
                $applyGroupFilter, $selectedUserGroups,
                $applyUserFilter, $selectedUsers,
                $applyDeductFromFilter, $selectedDeductFrom,
                $applyAddToFilter, $selectedAddTo,
                $applyCustomerFilter, $selectedCustomers,
                $applyVendorFilter, $selectedVendors,
                $voucherNoFilter
            ) {
                $this->applyDateFilter($q, $from_date, $to_date, 'date');

                if ($applyGroupFilter) {
                    $q->where(function ($sub) use ($selectedUserGroups) {
                        foreach ($selectedUserGroups as $gid) {
                            $sub->orWhereJsonContains('user_group_ids', (int) $gid)
                                ->orWhereJsonContains('user_group_ids', (string) $gid);
                        }
                    });
                }
                if ($applyUserFilter) {
                    $q->whereIn('created_by', $selectedUsers);
                }
                if ($applyDeductFromFilter) {
                    $q->whereIn('from_warehouse_id', $selectedDeductFrom);
                }
                if ($applyAddToFilter) {
                    $q->whereIn('to_warehouse_id', $selectedAddTo);
                }

                if ($applyCustomerFilter || $applyVendorFilter) {
                    $q->where(function ($pQ) use ($applyCustomerFilter, $selectedCustomers, $applyVendorFilter, $selectedVendors) {
                        if ($applyCustomerFilter && $applyVendorFilter) {
                            $pQ->where(function ($cQ) use ($selectedCustomers) {
                                $cQ->where('party_type', 'customer')->whereIn('party_id', $selectedCustomers);
                            })->orWhere(function ($vQ) use ($selectedVendors) {
                                $vQ->where('party_type', 'vendor')->whereIn('party_id', $selectedVendors);
                            });
                        } elseif ($applyCustomerFilter) {
                            $pQ->where('party_type', 'customer')->whereIn('party_id', $selectedCustomers);
                        } else {
                            $pQ->where('party_type', 'vendor')->whereIn('party_id', $selectedVendors);
                        }
                    });
                }

                if ($voucherNoFilter !== '') {
                    $q->where('voucher_no', 'like', "%{$voucherNoFilter}%");
                }
            });

        if ($applyItemFilter) {
            $acceptanceItemsQuery->whereIn('product_id', $selectedItems);
        }
        if ($btrFilter !== '') {
            $acceptanceItemsQuery->where('btr_no', 'like', "%{$btrFilter}%");
        }

        $acceptanceItems = $acceptanceItemsQuery->get();

        // Query ClaimItemReceiptItem
        $receiptItemsQuery = \App\Models\ClaimItemReceiptItem::with(['receipt.fromWarehouse', 'receipt.toWarehouse', 'receipt.vendor', 'receipt.customer', 'product.brandRelation'])
            ->whereHas('receipt', function ($q) use (
                $from_date, $to_date,
                $applyGroupFilter, $selectedUserGroups,
                $applyUserFilter, $selectedUsers,
                $applyDeductFromFilter, $selectedDeductFrom,
                $applyAddToFilter, $selectedAddTo,
                $applyCustomerFilter, $selectedCustomers,
                $applyVendorFilter, $selectedVendors,
                $voucherNoFilter
            ) {
                $this->applyDateFilter($q, $from_date, $to_date, 'date');

                if ($applyGroupFilter) {
                    $q->where(function ($sub) use ($selectedUserGroups) {
                        foreach ($selectedUserGroups as $gid) {
                            $sub->orWhereJsonContains('user_group_ids', (int) $gid)
                                ->orWhereJsonContains('user_group_ids', (string) $gid);
                        }
                    });
                }
                if ($applyUserFilter) {
                    $q->whereIn('created_by', $selectedUsers);
                }
                if ($applyDeductFromFilter) {
                    $q->whereIn('from_warehouse_id', $selectedDeductFrom);
                }
                if ($applyAddToFilter) {
                    $q->whereIn('to_warehouse_id', $selectedAddTo);
                }

                if ($applyCustomerFilter || $applyVendorFilter) {
                    $q->where(function ($pQ) use ($applyCustomerFilter, $selectedCustomers, $applyVendorFilter, $selectedVendors) {
                        if ($applyCustomerFilter && $applyVendorFilter) {
                            $pQ->where(function ($cQ) use ($selectedCustomers) {
                                $cQ->where('party_type', 'customer')->whereIn('party_id', $selectedCustomers);
                            })->orWhere(function ($vQ) use ($selectedVendors) {
                                $vQ->where('party_type', 'vendor')->whereIn('party_id', $selectedVendors);
                            });
                        } elseif ($applyCustomerFilter) {
                            $pQ->where('party_type', 'customer')->whereIn('party_id', $selectedCustomers);
                        } else {
                            $pQ->where('party_type', 'vendor')->whereIn('party_id', $selectedVendors);
                        }
                    });
                }

                if ($voucherNoFilter !== '') {
                    $q->where('voucher_no', 'like', "%{$voucherNoFilter}%");
                }
            });

        if ($applyItemFilter) {
            $receiptItemsQuery->whereIn('product_id', $selectedItems);
        }
        if ($btrFilter !== '') {
            $receiptItemsQuery->where('btr_no', 'like', "%{$btrFilter}%");
        }

        $receiptItems = $receiptItemsQuery->get();

        // Build data structure grouped by btr_no -> product_id
        $btrGroups = [];

        foreach ($acceptanceItems as $ai) {
            $btrNo = trim((string) $ai->btr_no);
            if ($btrNo === '') {
                $btrNo = 'N/A';
            }
            $productId = $ai->product_id;
            if (!isset($btrGroups[$btrNo])) {
                $btrGroups[$btrNo] = [];
            }
            if (!isset($btrGroups[$btrNo][$productId])) {
                $btrGroups[$btrNo][$productId] = [
                    'brand_name' => $ai->product?->brandRelation?->name ?? 'N/A',
                    'product_name' => $ai->product?->name ?? 'N/A',
                    'clm_acp' => 0.0,
                    'cir' => 0.0,
                    'entries' => [],
                ];
            }
            $qty = (float) ($ai->quantity ?? 0);
            $btrGroups[$btrNo][$productId]['clm_acp'] += $qty;
            $btrGroups[$btrNo][$productId]['entries'][] = [
                'type' => 'Claim Acceptance',
                'voucher_no' => $ai->voucher?->voucher_no ?? 'N/A',
                'date' => $ai->voucher?->date ? date('d-m-Y', strtotime($ai->voucher->date)) : 'N/A',
                'party_name' => $ai->voucher ? $ai->voucher->partyName() : 'N/A',
                'btr_no' => $ai->btr_no,
                'clm_acp' => $qty,
                'cir' => 0,
            ];
        }

        foreach ($receiptItems as $ri) {
            $btrNo = trim((string) $ri->btr_no);
            if ($btrNo === '') {
                $btrNo = 'N/A';
            }
            $productId = $ri->product_id;
            if (!isset($btrGroups[$btrNo])) {
                $btrGroups[$btrNo] = [];
            }
            if (!isset($btrGroups[$btrNo][$productId])) {
                $btrGroups[$btrNo][$productId] = [
                    'brand_name' => $ri->product?->brandRelation?->name ?? 'N/A',
                    'product_name' => $ri->product?->name ?? 'N/A',
                    'clm_acp' => 0.0,
                    'cir' => 0.0,
                    'entries' => [],
                ];
            }
            $qty = (float) ($ri->quantity ?? 0);
            $btrGroups[$btrNo][$productId]['cir'] += $qty;
            $btrGroups[$btrNo][$productId]['entries'][] = [
                'type' => 'Claim Item Receipt',
                'voucher_no' => $ri->receipt?->voucher_no ?? 'N/A',
                'date' => $ri->receipt?->date ? date('d-m-Y', strtotime($ri->receipt->date)) : 'N/A',
                'party_name' => $ri->receipt ? $ri->receipt->partyName() : 'N/A',
                'btr_no' => $ri->btr_no,
                'clm_acp' => 0,
                'cir' => $qty,
            ];
        }

        ksort($btrGroups);

        return view('admin_panel.reports.claim_item_receipt.preview_btr_wise', compact(
            'btrGroups',
            'from_date',
            'to_date'
        ));
    }
}

