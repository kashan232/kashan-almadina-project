<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\AdjustmentVoucher;
use App\Models\Customer;
use App\Models\Vendor;
use App\Traits\VoucherReportHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdjustmentVoucherReportController extends Controller
{
    use VoucherReportHelpers;

    public function index()
    {
        return view('admin_panel.reports.adjustment_voucher.index', $this->loadVoucherReportFilters());
    }

    public function preview(Request $request)
    {
        $report_type = $request->input('report_type', 'source_party');
        $entry_from = $request->entry_from;
        $entry_to = $request->entry_to;
        $grouped = $this->fetchReportLines($request)->groupBy('group_key');

        return view('admin_panel.reports.adjustment_voucher.preview', compact(
            'grouped', 'report_type', 'entry_from', 'entry_to'
        ));
    }

    private function fetchReportLines(Request $request): Collection
    {
        $mainHeads = $request->main_head ?? [];
        $subHeads = $request->sub_head ?? $request->account_head ?? [];
        $accounts = $request->account ?? [];
        $report_type = $request->input('report_type', 'source_party');
        $totalAccountHeads = AccountHead::count();
        $totalAccounts = Account::count();

        $query = AdjustmentVoucher::query()->withoutGlobalScopes();
        $this->applyPostedStatusFilter($query);
        $this->applyDateFilter($query, $request->entry_from, $request->entry_to, 'entry_date');
        $this->applyCommonVoucherFilters($query, $request, 'avid');
        $this->applyPartyFilter($query, $request, 'party_type', 'party_id');

        $lines = collect();
        foreach ($query->orderBy('entry_date')->orderBy('id')->get() as $voucher) {
            $lines = $lines->merge($this->expandLines($voucher, $report_type, $mainHeads, $subHeads, $accounts, $totalAccountHeads, $totalAccounts));
        }

        return $lines->sortBy(fn ($line) => sprintf(
            '%s-%s-%s',
            $line->sort_group ?? '',
            $line->voucher_date ?? '',
            str_pad((string) ($line->voucher_no ?? ''), 10, '0', STR_PAD_LEFT)
        ))->values();
    }

    private function expandLines(
        AdjustmentVoucher $voucher,
        string $reportType,
        array $mainHeads,
        array $subHeads,
        array $selectedAccounts,
        int $totalAccountHeads,
        int $totalAccounts
    ): Collection {
        $partyName = $this->resolvePartyName($voucher->party_type, $voucher->party_id);
        $narrationIds = json_decode($voucher->narration_id, true) ?? [];
        $rowHeads = json_decode($voucher->account_head, true) ?? [];
        $rowAccounts = json_decode($voucher->account_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];
        $lines = collect();

        foreach ($narrationIds as $index => $narrId) {
            $headId = $rowHeads[$index] ?? null;
            $headName = $headId ? (AccountHead::find($headId)?->name ?? 'N/A') : 'N/A';
            $accountId = $rowAccounts[$index] ?? null;

            if (!$this->passesHeadAccountFilter($mainHeads, $subHeads, $selectedAccounts, $headId, $accountId, $totalAccountHeads, $totalAccounts)) {
                continue;
            }

            $amount = (float) ($amounts[$index] ?? 0);
            if ($amount == 0.0 && empty($narrId) && empty($accountId)) {
                continue;
            }

            $account = !empty($accountId) ? Account::find($accountId) : null;
            $destLabel = strtoupper($account->title ?? $headName);
            $groupKey = $reportType === 'destination_account'
                ? 'head_' . ($headId ?: '0') . '_' . ($accountId ?: '0')
                : 'party_' . $voucher->party_type . '_' . $voucher->party_id;
            $groupLabel = $reportType === 'destination_account' ? $destLabel : $partyName;

            $lines->push((object) [
                'group_key' => $groupKey,
                'group_label' => $groupLabel,
                'sort_group' => $groupLabel,
                'voucher_no' => $voucher->avid,
                'voucher_date' => $voucher->entry_date,
                'reference_no' => $references[$index] ?? '',
                'narration' => $this->resolveNarration($narrId),
                'party_name' => $partyName,
                'account_name' => $destLabel,
                'sub_head_name' => $headName,
                'amount' => $amount,
            ]);
        }

        return $lines;
    }
}
