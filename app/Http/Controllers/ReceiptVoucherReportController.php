<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\Narration;
use App\Models\ReceiptsVoucher;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReceiptVoucherReportController extends Controller
{
    private function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    private function applyDateFilter($query, ?string $from, ?string $to, string $column): void
    {
        if (!empty($from)) {
            $query->whereDate($column, '>=', $from);
        }
        if (!empty($to)) {
            $query->whereDate($column, '<=', $to);
        }
    }

    private function applyIndependentDateFilters($query, ?string $receiptFrom, ?string $receiptTo, ?string $entryFrom, ?string $entryTo): void
    {
        if (!empty($receiptFrom) || !empty($receiptTo)) {
            $this->applyDateFilter($query, $receiptFrom, $receiptTo, 'receipt_date');
        }
        if (!empty($entryFrom) || !empty($entryTo)) {
            $this->applyDateFilter($query, $entryFrom, $entryTo, 'entry_date');
        }
    }

    private function resolvePartyName(ReceiptsVoucher $voucher): string
    {
        if (is_numeric($voucher->type)) {
            $account = Account::find($voucher->party_id);

            return strtoupper($account->title ?? 'N/A');
        }

        if ($voucher->type === 'vendor') {
            $vendor = Vendor::find($voucher->party_id);

            return strtoupper($vendor->name ?? 'N/A');
        }

        if (in_array($voucher->type, ['customer', 'walkin'], true)) {
            $customer = Customer::find($voucher->party_id);

            return strtoupper($customer->customer_name ?? 'N/A');
        }

        return 'N/A';
    }

    public function index()
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $users = User::with('userGroups')->orderBy('name')->get();
        $accountHeads = AccountHead::where('status', 1)->orderBy('name')->get();
        $accounts = Account::with('head')->orderBy('title')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view('admin_panel.reports.receipt_voucher.index', compact(
            'userGroups',
            'users',
            'accountHeads',
            'accounts',
            'customers',
            'vendors',
            'shopGroupIds'
        ));
    }

    public function preview(Request $request)
    {
        $report_type = $request->input('report_type', 'source_party');
        $receipt_from = $request->receipt_from;
        $receipt_to = $request->receipt_to;
        $entry_from = $request->entry_from;
        $entry_to = $request->entry_to;
        $lines = $this->fetchReportLines($request);
        $grouped = $lines->groupBy('group_key');

        return view('admin_panel.reports.receipt_voucher.preview', compact(
            'grouped',
            'report_type',
            'receipt_from',
            'receipt_to',
            'entry_from',
            'entry_to'
        ));
    }

    private function fetchReportLines(Request $request): Collection
    {
        $user_groups = $request->user_group ?? [];
        $officers = $request->sales_officer ?? [];
        $partyTypes = $request->party_type ?? [];
        $parties = $request->party ?? [];
        $mainHeads = $request->main_head ?? [];
        $accounts = $request->account ?? [];
        $voucherId = $request->voucher_id;
        $report_type = $request->input('report_type', 'source_party');
        $receipt_from = $request->receipt_from;
        $receipt_to = $request->receipt_to;
        $entry_from = $request->entry_from;
        $entry_to = $request->entry_to;

        $totalGroups = UserGroup::count();
        $totalUsers = User::count();
        $totalParties = Customer::count() + Vendor::count();
        $totalAccountHeads = AccountHead::count();
        $totalAccounts = Account::count();

        $query = ReceiptsVoucher::query()->withoutGlobalScopes()->whereIn('status', ['posted', 'Posted']);

        $this->applyIndependentDateFilters($query, $receipt_from, $receipt_to, $entry_from, $entry_to);

        if (!empty($voucherId)) {
            $query->where(function ($sub) use ($voucherId) {
                $sub->where('rvid', 'like', "%{$voucherId}%")
                    ->orWhere('id', 'like', "%{$voucherId}%")
                    ->orWhere('rvid', 'like', '%' . ltrim($voucherId, '0') . '%');
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

        if (!empty($partyTypes) && count($partyTypes) < 3) {
            $query->where(function ($sub) use ($partyTypes) {
                foreach ($partyTypes as $partyType) {
                    if ($partyType === 'vendor') {
                        $sub->orWhere('type', 'vendor');
                    } elseif ($partyType === 'customer') {
                        $sub->orWhere('type', 'customer');
                    } elseif ($partyType === 'walkin') {
                        $sub->orWhere('type', 'walkin');
                    }
                }
            });
        }

        if ($this->shouldApplyFilter($parties, $totalParties)) {
            $query->where(function ($sub) use ($parties) {
                foreach ($parties as $party) {
                    if (!str_contains($party, ':')) {
                        continue;
                    }
                    [$type, $id] = explode(':', $party, 2);
                    $sub->orWhere(function ($sq) use ($type, $id) {
                        $sq->where('type', $type)->where('party_id', $id);
                    });
                }
            });
        }

        $vouchers = $query->orderBy('receipt_date')->orderBy('id')->get();
        $lines = collect();

        foreach ($vouchers as $voucher) {
            $lines = $lines->merge($this->expandVoucherLines(
                $voucher,
                $report_type,
                $mainHeads,
                $accounts,
                $totalAccountHeads,
                $totalAccounts
            ));
        }

        return $lines->sortBy(fn ($line) => sprintf(
            '%s-%s-%s',
            $line->sort_group ?? '',
            $line->receipt_date ?? '',
            str_pad((string) ($line->rvid ?? ''), 10, '0', STR_PAD_LEFT)
        ))->values();
    }

    private function expandVoucherLines(
        ReceiptsVoucher $voucher,
        string $reportType,
        array $mainHeads,
        array $selectedAccounts,
        int $totalAccountHeads,
        int $totalAccounts
    ): Collection {
        $partyName = $this->resolvePartyName($voucher);
        $narrationIds = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $rowHeads = json_decode($voucher->row_account_head, true) ?? [];
        $rowAccounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        $lines = collect();

        foreach ($narrationIds as $index => $narrId) {
            $headId = $rowHeads[$index] ?? null;
            $headName = $headId ? (AccountHead::find($headId)?->name ?? 'N/A') : 'N/A';

            $activeHeadFilter = $mainHeads;
            if ($this->shouldApplyFilter($activeHeadFilter, $totalAccountHeads) && !in_array((string) $headId, array_map('strval', $activeHeadFilter), true)) {
                continue;
            }

            $accountId = $rowAccounts[$index] ?? null;
            if ($this->shouldApplyFilter($selectedAccounts, $totalAccounts) && !in_array((string) $accountId, array_map('strval', $selectedAccounts), true)) {
                continue;
            }

            $narrationText = 'N/A';
            if (!empty($narrId)) {
                if (is_numeric($narrId)) {
                    $narrationText = Narration::find($narrId)?->narration ?? 'N/A';
                } else {
                    $narrationText = $narrId;
                }
            }

            $account = !empty($accountId) ? Account::find($accountId) : null;
            $amount = (float) ($amounts[$index] ?? 0);

            if ($amount == 0.0 && empty($narrId) && empty($rowAccounts[$index])) {
                continue;
            }

            $groupKey = in_array($reportType, ['sub_head', 'main_head'], true)
                ? 'head_' . ($headId ?: '0')
                : 'party_' . $voucher->type . '_' . $voucher->party_id;

            $groupLabel = in_array($reportType, ['sub_head', 'main_head'], true) ? strtoupper($headName) : $partyName;

            $lines->push((object) [
                'group_key' => $groupKey,
                'group_label' => $groupLabel,
                'sort_group' => $groupLabel,
                'voucher_id' => $voucher->id,
                'rvid' => $voucher->rvid,
                'receipt_date' => $voucher->receipt_date,
                'reference_no' => $references[$index] ?? '',
                'narration' => $narrationText,
                'sub_head_name' => $headName,
                'party_name' => $partyName,
                'bank_details' => $account->title ?? '-',
                'amount' => $amount,
            ]);
        }

        return $lines;
    }
}
